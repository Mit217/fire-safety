<?php
// Smart Fire Safety Compliance Database - single-file frontend (PHP + OCI8 + Oracle 19c)

$conn = @oci_connect('FIRESAFETY', 'FireSafety2026', 'localhost:1521/orclpdb');

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Run a query and return all rows as an array of associative arrays (column names are UPPERCASE)
function q($conn, $sql) {
    $st = oci_parse($conn, $sql);
    if (!$st || !@oci_execute($st)) return [];
    oci_fetch_all($st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
    oci_free_statement($st);
    return $rows;
}

function scalar($conn, $sql) {
    $r = q($conn, $sql);
    return $r ? (int)array_values($r[0])[0] : 0;
}

// Status -> badge colour class
$badgeMap = [
    'PASS' => 'ok', 'COMPLETED' => 'ok',
    'FAIL' => 'bad', 'OPEN' => 'bad', 'CRITICAL' => 'crit', 'HIGH' => 'high',
    'PENDING' => 'warn', 'IN_PROGRESS' => 'warn', 'MEDIUM' => 'warn', 'LOW' => 'low',
];

function badge($v) {
    global $badgeMap;
    if ($v === null || $v === '') return '<span class="muted">-</span>';
    $cls = $badgeMap[$v] ?? 'low';
    return '<span class="badge ' . $cls . '">' . h(str_replace('_', ' ', $v)) . '</span>';
}

// $cols: label => column key.  $badges: column keys shown as status badges.
function table($rows, $cols, $badges = []) {
    if (!$rows) { echo '<p class="empty">No records found.</p>'; return; }
    echo '<div class="scroll"><table><thead><tr>';
    foreach ($cols as $label => $key) echo '<th>' . h($label) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($cols as $key) {
            $v = $r[$key] ?? null;
            if (in_array($key, $badges, true)) echo '<td>' . badge($v) . '</td>';
            else echo '<td>' . ($v === null || $v === '' ? '<span class="muted">-</span>' : h($v)) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

$connected = (bool)$conn;
$err = $connected ? null : oci_error();

if ($connected) {
    $totalBuildings   = scalar($conn, 'SELECT COUNT(*) FROM building');
    $totalInspections = scalar($conn, 'SELECT COUNT(*) FROM inspection');
    $totalViolations  = scalar($conn, 'SELECT COUNT(*) FROM violation');
    $openViolations   = scalar($conn, "SELECT COUNT(*) FROM violation WHERE status = 'OPEN'");

    // Uses the PL/SQL function get_violation_count
    $buildings = q($conn, "SELECT building_id, building_name, address, building_type, floors,
                                  get_violation_count(building_id) AS VIOLATIONS
                           FROM building ORDER BY building_id");

    $inspections = q($conn, "SELECT i.inspection_id, b.building_name, ins.inspector_name,
                                    TO_CHAR(i.inspection_date, 'DD Mon YYYY') AS INSPECTION_DATE, i.status
                             FROM inspection i
                             JOIN building b ON b.building_id = i.building_id
                             JOIN inspector ins ON ins.inspector_id = i.inspector_id
                             ORDER BY i.inspection_date DESC");

    $violations = q($conn, "SELECT v.violation_id, b.building_name, v.violation_type, v.severity, v.status
                            FROM violation v
                            JOIN inspection i ON i.inspection_id = v.inspection_id
                            JOIN building b ON b.building_id = i.building_id
                            ORDER BY v.violation_id");

    $actions = q($conn, "SELECT ca.action_id, b.building_name, v.violation_type, ca.action_description, ca.status
                         FROM corrective_action ca
                         JOIN violation v ON v.violation_id = ca.violation_id
                         JOIN inspection i ON i.inspection_id = v.inspection_id
                         JOIN building b ON b.building_id = i.building_id
                         ORDER BY ca.action_id");

    $report = q($conn, "SELECT building_name, TO_CHAR(inspection_date, 'DD Mon YYYY') AS INSPECTION_DATE,
                               inspection_status, violation_type, severity, violation_status,
                               action_description, action_status
                        FROM safety_report ORDER BY inspection_date DESC, building_name");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Smart Fire Safety Compliance Database</title>
<style>
  :root {
    --ink: #221c1b;
    --muted: #6f6664;
    --paper: #f6f4f3;
    --card: #ffffff;
    --line: #e4dedc;
    --ember: #b3261e;
    --ember-dark: #7d1913;
    --amber: #e59a12;
    --green: #2e7d4f;
    --yellow: #d9b100;
  }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--paper); color: var(--ink);
         font-family: "Segoe UI", Tahoma, Arial, sans-serif; line-height: 1.5; }
  header { background: var(--ember-dark); color: #fff; padding: 28px 24px 24px; }
  .wrap { max-width: 1100px; margin: 0 auto; }
  header h1 { margin: 0 0 4px; font-size: 1.9rem; letter-spacing: .2px; }
  header p { margin: 0; color: #f3d3cf; }
  main { padding: 24px 16px 48px; }
  section { margin-top: 36px; }
  h2 { font-size: 1.3rem; margin: 0 0 12px; }
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
  .stat { background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 16px 18px; }
  .stat b { display: block; font-size: 2.2rem; line-height: 1.1; }
  .stat span { color: var(--muted); font-size: .92rem; }
  .stat.alert b { color: var(--ember); }
  .scroll { overflow-x: auto; background: var(--card); border: 1px solid var(--line); border-radius: 8px; }
  table { width: 100%; border-collapse: collapse; font-size: .93rem; }
  th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
  th { background: #efeae8; font-weight: 600; white-space: nowrap; }
  tbody tr:last-child td { border-bottom: 0; }
  tbody tr:hover { background: #fbf6f5; }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: .8rem; font-weight: 600; white-space: nowrap; }
  .badge.ok   { background: #dcefe3; color: #1d5b37; }
  .badge.bad  { background: #f8d9d6; color: #8c1a13; }
  .badge.crit { background: var(--ember); color: #fff; }
  .badge.high { background: #fbe0b3; color: #7a4a00; }
  .badge.warn { background: #fdf0b8; color: #6b5600; }
  .badge.low  { background: #e6eadb; color: #4a5a24; }
  .muted { color: var(--muted); }
  .empty { color: var(--muted); }
  .error { background: #f8d9d6; border: 1px solid var(--ember); color: #6f120c; padding: 16px; border-radius: 8px; margin-top: 24px; }
  footer { text-align: center; color: var(--muted); font-size: .85rem; padding: 0 16px 28px; }
</style>
</head>
<body>

<header>
  <div class="wrap">
    <h1>Smart Fire Safety Compliance Database</h1>
    <p>Buildings, inspections, violations and corrective actions in one place.</p>
  </div>
</header>

<?php if (!$connected): ?>
<main><div class="wrap">
  <div class="error">
    <strong>Could not connect to Oracle.</strong><br>
    <?= h($err['message'] ?? 'Unknown error') ?><br>
    Check that the Oracle service is running, the listener is up, and OCI8 is enabled in php.ini.
  </div>
</div></main>
<?php else: ?>

<main><div class="wrap">

  <section id="dashboard" style="margin-top:0">
    <h2>Dashboard</h2>
    <div class="stats">
      <div class="stat"><b><?= $totalBuildings ?></b><span>Buildings registered</span></div>
      <div class="stat"><b><?= $totalInspections ?></b><span>Inspections carried out</span></div>
      <div class="stat"><b><?= $totalViolations ?></b><span>Violations recorded</span></div>
      <div class="stat alert"><b><?= $openViolations ?></b><span>Violations still open</span></div>
    </div>
  </section>

  <section id="buildings">
    <h2>Buildings</h2>
    <?php table($buildings, [
        'ID' => 'BUILDING_ID', 'Building' => 'BUILDING_NAME', 'Location' => 'ADDRESS',
        'Type' => 'BUILDING_TYPE', 'Floors' => 'FLOORS', 'Violations' => 'VIOLATIONS'
    ]); ?>
  </section>

  <section id="inspections">
    <h2>Inspections</h2>
    <?php table($inspections, [
        'ID' => 'INSPECTION_ID', 'Building' => 'BUILDING_NAME', 'Inspector' => 'INSPECTOR_NAME',
        'Date' => 'INSPECTION_DATE', 'Result' => 'STATUS'
    ], ['STATUS']); ?>
  </section>

  <section id="violations">
    <h2>Violations</h2>
    <?php table($violations, [
        'ID' => 'VIOLATION_ID', 'Building' => 'BUILDING_NAME', 'Violation' => 'VIOLATION_TYPE',
        'Severity' => 'SEVERITY', 'Status' => 'STATUS'
    ], ['SEVERITY', 'STATUS']); ?>
  </section>

  <section id="actions">
    <h2>Corrective actions</h2>
    <?php table($actions, [
        'ID' => 'ACTION_ID', 'Building' => 'BUILDING_NAME', 'Violation' => 'VIOLATION_TYPE',
        'Action' => 'ACTION_DESCRIPTION', 'Status' => 'STATUS'
    ], ['STATUS']); ?>
  </section>

  <section id="report">
    <h2>Safety report</h2>
    <p class="muted" style="margin-top:0">Combined view of every inspection, its violations and the actions taken (from the <code>safety_report</code> view).</p>
    <?php table($report, [
        'Building' => 'BUILDING_NAME', 'Inspected' => 'INSPECTION_DATE', 'Result' => 'INSPECTION_STATUS',
        'Violation' => 'VIOLATION_TYPE', 'Severity' => 'SEVERITY', 'Violation status' => 'VIOLATION_STATUS',
        'Action' => 'ACTION_DESCRIPTION', 'Action status' => 'ACTION_STATUS'
    ], ['INSPECTION_STATUS', 'SEVERITY', 'VIOLATION_STATUS', 'ACTION_STATUS']); ?>
  </section>

</div></main>

<footer>Oracle Database 19c &middot; PHP <?= h(PHP_VERSION) ?> &middot; OCI8</footer>

<?php oci_close($conn); endif; ?>
</body>
</html>
