<?php
// Smart Fire Safety Compliance Database - single-file frontend with basic CRUD (PHP + OCI8 + Oracle 19c)

$conn = @oci_connect('FIRESAFETY', 'FireSafety2026', 'localhost:1521/orclpdb');

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Run a query (optionally with bind variables) and return rows as associative arrays (UPPERCASE keys)
function q($conn, $sql, $binds = []) {
    $st = oci_parse($conn, $sql);
    if (!$st) return [];
    foreach (array_keys($binds) as $k) oci_bind_by_name($st, $k, $binds[$k]);
    if (!@oci_execute($st)) return [];
    oci_fetch_all($st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
    oci_free_statement($st);
    return $rows;
}

function scalar($conn, $sql) {
    $r = q($conn, $sql);
    return $r ? (int)array_values($r[0])[0] : 0;
}

// ---------- Table definitions used by the forms ----------
$E = [
    'building' => ['title' => 'Building', 'plural' => 'Buildings', 'table' => 'building', 'pk' => 'building_id',
        'fields' => [
            'building_name' => ['label' => 'Building name', 'type' => 'text', 'required' => true, 'max' => 100],
            'address'       => ['label' => 'Location', 'type' => 'text', 'max' => 200],
            'building_type' => ['label' => 'Type', 'type' => 'text', 'max' => 50],
            'floors'        => ['label' => 'Floors', 'type' => 'number'],
        ]],
    'inspector' => ['title' => 'Inspector', 'plural' => 'Inspectors', 'table' => 'inspector', 'pk' => 'inspector_id',
        'fields' => [
            'inspector_name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
            'phone'          => ['label' => 'Phone', 'type' => 'text', 'max' => 15],
        ]],
    'inspection' => ['title' => 'Inspection', 'plural' => 'Inspections', 'table' => 'inspection', 'pk' => 'inspection_id',
        'fields' => [
            'building_id'     => ['label' => 'Building', 'type' => 'fk', 'ref' => 'building', 'required' => true],
            'inspector_id'    => ['label' => 'Inspector', 'type' => 'fk', 'ref' => 'inspector', 'required' => true],
            'inspection_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
            'status'          => ['label' => 'Result', 'type' => 'enum', 'required' => true,
                                  'options' => ['PASS', 'FAIL', 'PENDING']],
        ]],
    'violation' => ['title' => 'Violation', 'plural' => 'Violations', 'table' => 'violation', 'pk' => 'violation_id',
        'fields' => [
            'inspection_id'  => ['label' => 'Inspection', 'type' => 'fk', 'ref' => 'inspection', 'required' => true],
            'violation_type' => ['label' => 'Violation', 'type' => 'text', 'required' => true, 'max' => 100],
            'severity'       => ['label' => 'Severity', 'type' => 'enum', 'required' => true,
                                 'options' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']],
            'status'         => ['label' => 'Status', 'type' => 'enum', 'required' => true,
                                 'options' => ['OPEN', 'IN_PROGRESS', 'COMPLETED']],
        ]],
    'corrective_action' => ['title' => 'Corrective action', 'plural' => 'Corrective actions',
        'table' => 'corrective_action', 'pk' => 'action_id',
        'fields' => [
            'violation_id'       => ['label' => 'Violation', 'type' => 'fk', 'ref' => 'violation', 'required' => true],
            'action_description' => ['label' => 'Action', 'type' => 'text', 'required' => true, 'max' => 200],
            'status'             => ['label' => 'Status', 'type' => 'enum', 'required' => true,
                                     'options' => ['PENDING', 'IN_PROGRESS', 'COMPLETED']],
        ]],
];

// Dropdown sources for foreign keys
$FK = [
    'building'   => "SELECT building_id AS ID, building_name AS LABEL FROM building ORDER BY 1",
    'inspector'  => "SELECT inspector_id AS ID, inspector_name AS LABEL FROM inspector ORDER BY 1",
    'inspection' => "SELECT i.inspection_id AS ID,
                            '#' || i.inspection_id || ' - ' || b.building_name || ' (' || TO_CHAR(i.inspection_date, 'YYYY-MM-DD') || ')' AS LABEL
                     FROM inspection i JOIN building b ON b.building_id = i.building_id ORDER BY 1",
    'violation'  => "SELECT violation_id AS ID, '#' || violation_id || ' - ' || violation_type AS LABEL
                     FROM violation ORDER BY 1",
];

// ---------- Handle add / edit / delete ----------
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ent = $_POST['entity'] ?? '';
    $act = $_POST['action'] ?? '';
    $ok = null; $err = null;

    if (!isset($E[$ent]) || !in_array($act, ['create', 'update', 'delete'], true)) {
        $err = 'Invalid request.';
    } else {
        $cfg = $E[$ent]; $t = $cfg['table']; $pk = $cfg['pk'];
        $id  = $_POST['id'] ?? '';

        if ($act === 'delete') {
            $st = oci_parse($conn, "DELETE FROM $t WHERE $pk = :b_id");
            oci_bind_by_name($st, ':b_id', $id);
            if (@oci_execute($st)) {
                $ok = $cfg['title'] . ' deleted.';
            } else {
                $e = oci_error($st);
                $err = ($e['code'] ?? 0) == 2292
                    ? 'Cannot delete this ' . strtolower($cfg['title']) . ': other records still depend on it. Remove those first.'
                    : 'Delete failed: ' . ($e['message'] ?? 'unknown error');
            }
        } else {
            // Validate the submitted fields
            $vals = []; $errs = [];
            foreach ($cfg['fields'] as $f => $s) {
                $v = trim($_POST[$f] ?? '');
                if ($v === '') {
                    if (!empty($s['required'])) $errs[] = $s['label'] . ' is required.';
                    $vals[$f] = null;
                    continue;
                }
                if ($s['type'] === 'number' || $s['type'] === 'fk') {
                    if (!ctype_digit($v)) $errs[] = $s['label'] . ' must be a whole number.';
                } elseif ($s['type'] === 'date') {
                    $d = DateTime::createFromFormat('Y-m-d', $v);
                    if (!$d || $d->format('Y-m-d') !== $v) $errs[] = $s['label'] . ' is not a valid date.';
                } elseif ($s['type'] === 'enum') {
                    if (!in_array($v, $s['options'], true)) $errs[] = $s['label'] . ' is not a valid choice.';
                } elseif (isset($s['max']) && strlen($v) > $s['max']) {
                    $errs[] = $s['label'] . ' is too long (max ' . $s['max'] . ' characters).';
                }
                $vals[$f] = $v;
            }
            if ($act === 'update' && !ctype_digit($id)) $errs[] = 'Missing record ID.';

            if ($errs) {
                $err = implode(' ', $errs);
            } else {
                $cols = array_keys($vals);
                $ph = [];
                foreach ($cols as $c) {
                    $ph[$c] = $cfg['fields'][$c]['type'] === 'date' ? "TO_DATE(:b_$c, 'YYYY-MM-DD')" : ":b_$c";
                }
                if ($act === 'create') {
                    $id = scalar($conn, "SELECT NVL(MAX($pk), 0) + 1 FROM $t");
                    $sql = "INSERT INTO $t ($pk, " . implode(', ', $cols) . ") VALUES (:b_id, " . implode(', ', $ph) . ")";
                } else {
                    $sets = [];
                    foreach ($cols as $c) $sets[] = "$c = " . $ph[$c];
                    $sql = "UPDATE $t SET " . implode(', ', $sets) . " WHERE $pk = :b_id";
                }
                $st = oci_parse($conn, $sql);
                oci_bind_by_name($st, ':b_id', $id);
                foreach ($cols as $c) oci_bind_by_name($st, ":b_$c", $vals[$c]);
                if (@oci_execute($st)) {
                    $ok = $cfg['title'] . ($act === 'create' ? ' added.' : ' updated.');
                } else {
                    $e = oci_error($st);
                    $err = 'Save failed: ' . ($e['message'] ?? 'unknown error');
                }
            }
        }
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($err ? ['err' => $err] : ['ok' => $ok]) . '#' . ($ent ?: 'top'));
    exit;
}

// ---------- Display helpers ----------
$badgeMap = [
    'PASS' => 'ok', 'COMPLETED' => 'ok',
    'FAIL' => 'bad', 'OPEN' => 'bad', 'CRITICAL' => 'crit', 'HIGH' => 'high',
    'PENDING' => 'warn', 'IN_PROGRESS' => 'warn', 'MEDIUM' => 'warn', 'LOW' => 'low', 'MODERATE' => 'warn',
];

function badge($v) {
    global $badgeMap;
    if ($v === null || $v === '') return '<span class="muted">-</span>';
    return '<span class="badge ' . ($badgeMap[$v] ?? 'low') . '">' . h(str_replace('_', ' ', $v)) . '</span>';
}

// $cols: label => column key. $badges: column keys shown as badges. $ent: adds Edit/Delete buttons.
function table($rows, $cols, $badges = [], $ent = null) {
    global $E;
    if (!$rows) { echo '<p class="empty">No records found.</p>'; return; }
    echo '<div class="scroll"><table><thead><tr>';
    foreach ($cols as $label => $key) echo '<th>' . h($label) . '</th>';
    if ($ent) echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($cols as $key) {
            $v = $r[$key] ?? null;
            if (in_array($key, $badges, true)) echo '<td>' . badge($v) . '</td>';
            else echo '<td>' . ($v === null || $v === '' ? '<span class="muted">-</span>' : h($v)) . '</td>';
        }
        if ($ent) {
            $id = $r[strtoupper($E[$ent]['pk'])];
            echo '<td class="act"><a class="btn ghost sm" href="?edit=' . h($ent) . '&amp;id=' . h($id) . '#' . h($ent) . '">Edit</a> '
               . '<form method="post" onsubmit="return confirm(\'Delete this record?\')">'
               . '<input type="hidden" name="entity" value="' . h($ent) . '">'
               . '<input type="hidden" name="action" value="delete">'
               . '<input type="hidden" name="id" value="' . h($id) . '">'
               . '<button class="btn danger sm" type="submit">Delete</button></form></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function select_html($name, $options, $current, $required) {
    $out = '<select name="' . h($name) . '"' . ($required ? ' required' : '') . '><option value="">Select...</option>';
    foreach ($options as $val => $label) {
        $sel = ((string)$val === (string)$current) ? ' selected' : '';
        $out .= '<option value="' . h($val) . '"' . $sel . '>' . h($label) . '</option>';
    }
    return $out . '</select>';
}

// Add form (no $row) or edit form ($row = existing record)
function crud_form($conn, $FK, $cfg, $ent, $row = null) {
    $edit = $row !== null;
    echo '<form method="post" class="crud">'
       . '<input type="hidden" name="entity" value="' . h($ent) . '">'
       . '<input type="hidden" name="action" value="' . ($edit ? 'update' : 'create') . '">';
    if ($edit) echo '<input type="hidden" name="id" value="' . h($row[strtoupper($cfg['pk'])]) . '">';
    echo '<div class="fields">';
    foreach ($cfg['fields'] as $f => $s) {
        $val = $edit ? ($row[strtoupper($f)] ?? '') : '';
        $req = !empty($s['required']);
        echo '<label><span>' . h($s['label']) . ($req ? ' *' : '') . '</span>';
        if ($s['type'] === 'fk') {
            $opts = [];
            foreach (q($conn, $FK[$s['ref']]) as $o) $opts[$o['ID']] = $o['LABEL'];
            echo select_html($f, $opts, $val, $req);
        } elseif ($s['type'] === 'enum') {
            $opts = [];
            foreach ($s['options'] as $o) $opts[$o] = str_replace('_', ' ', $o);
            echo select_html($f, $opts, $val, $req);
        } elseif ($s['type'] === 'date') {
            echo '<input type="date" name="' . h($f) . '" value="' . h($val) . '"' . ($req ? ' required' : '') . '>';
        } elseif ($s['type'] === 'number') {
            echo '<input type="number" min="0" step="1" name="' . h($f) . '" value="' . h($val) . '">';
        } else {
            echo '<input type="text" name="' . h($f) . '" value="' . h($val) . '" maxlength="' . (int)($s['max'] ?? 200) . '"' . ($req ? ' required' : '') . '>';
        }
        echo '</label>';
    }
    echo '</div><div class="btns"><button class="btn" type="submit">' . ($edit ? 'Save changes' : 'Add ' . h(strtolower($cfg['title']))) . '</button>';
    if ($edit) echo ' <a class="btn ghost" href="?#' . h($ent) . '">Cancel</a>';
    echo '</div></form>';
}

// One full section: heading, add/edit form, table
function crud_section($conn, $E, $FK, $ent, $rows, $cols, $badges = []) {
    $cfg = $E[$ent];
    echo '<section id="' . h($ent) . '"><h2>' . h($cfg['plural']) . '</h2>';

    $editId = (($_GET['edit'] ?? '') === $ent && ctype_digit($_GET['id'] ?? '')) ? $_GET['id'] : null;
    if ($editId !== null) {
        $sel = [$cfg['pk']];
        foreach ($cfg['fields'] as $f => $s) $sel[] = $s['type'] === 'date' ? "TO_CHAR($f, 'YYYY-MM-DD') AS $f" : $f;
        $r = q($conn, 'SELECT ' . implode(', ', $sel) . " FROM {$cfg['table']} WHERE {$cfg['pk']} = :id", [':id' => $editId]);
        if ($r) {
            echo '<div class="formbox"><h3>Edit ' . h(strtolower($cfg['title'])) . ' #' . h($editId) . '</h3>';
            crud_form($conn, $FK, $cfg, $ent, $r[0]);
            echo '</div>';
        } else {
            echo '<p class="empty">That record no longer exists.</p>';
        }
    } else {
        echo '<details class="formbox"><summary>+ Add ' . h(strtolower($cfg['title'])) . '</summary>';
        crud_form($conn, $FK, $cfg, $ent);
        echo '</details>';
    }
    table($rows, $cols, $badges, $ent);
    echo '</section>';
}

// ---------- Load data ----------
$connected = (bool)$conn;
$err = $connected ? null : oci_error();

if ($connected) {
    $totalBuildings   = scalar($conn, 'SELECT COUNT(*) FROM building');
    $totalInspections = scalar($conn, 'SELECT COUNT(*) FROM inspection');
    $totalViolations  = scalar($conn, 'SELECT COUNT(*) FROM violation');
    $openViolations   = scalar($conn, "SELECT COUNT(*) FROM violation WHERE status = 'OPEN'");

    // Uses PL/SQL functions get_violation_count and get_building_risk_level
    $buildings = q($conn, "SELECT building_id, building_name, address, building_type, floors,
                                  get_violation_count(building_id) AS VIOLATIONS,
                                  get_building_risk_level(building_id) AS RISK_LEVEL
                           FROM building ORDER BY building_id");

    $inspectors = q($conn, "SELECT inspector_id, inspector_name, phone FROM inspector ORDER BY inspector_id");

    $inspections = q($conn, "SELECT i.inspection_id, b.building_name, ins.inspector_name,
                                    TO_CHAR(i.inspection_date, 'DD Mon YYYY') AS INSPECTION_DATE, i.status
                             FROM inspection i
                             JOIN building b ON b.building_id = i.building_id
                             JOIN inspector ins ON ins.inspector_id = i.inspector_id
                             ORDER BY i.inspection_id ASC");

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
$flashOk  = $_GET['ok'] ?? null;
$flashErr = $_GET['err'] ?? null;
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

  .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid; }
  .flash.ok  { background: #dcefe3; border-color: #2e7d4f; color: #1d5b37; }
  .flash.err { background: #f8d9d6; border-color: var(--ember); color: #6f120c; }

  .formbox { background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 12px 16px; margin-bottom: 12px; }
  .formbox h3 { margin: 0 0 10px; font-size: 1.05rem; }
  .formbox summary { cursor: pointer; font-weight: 600; color: var(--ember); }
  .formbox[open] summary { margin-bottom: 12px; }
  .fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; }
  .crud label span { display: block; font-size: .85rem; color: var(--muted); margin-bottom: 2px; }
  .crud input, .crud select { width: 100%; padding: 8px 10px; border: 1px solid #cfc7c4; border-radius: 6px;
                              font: inherit; background: #fff; color: var(--ink); }
  .crud input:focus, .crud select:focus { outline: 2px solid var(--ember); outline-offset: 0; border-color: var(--ember); }
  .btns { margin-top: 14px; }
  .btn { display: inline-block; padding: 8px 16px; border: 1px solid var(--ember); border-radius: 6px; background: var(--ember);
         color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; }
  .btn:hover { background: var(--ember-dark); border-color: var(--ember-dark); }
  .btn.ghost { background: #fff; color: var(--ember); }
  .btn.ghost:hover { background: #fbeceb; }
  .btn.danger { background: #fff; color: var(--ember); }
  .btn.danger:hover { background: var(--ember); color: #fff; }
  .btn.sm { padding: 3px 10px; font-size: .85rem; }
  .btn:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
  td.act { white-space: nowrap; }
  td.act form { display: inline; }

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
  .muted, .empty { color: var(--muted); }
  .error { background: #f8d9d6; border: 1px solid var(--ember); color: #6f120c; padding: 16px; border-radius: 8px; margin-top: 24px; }
  footer { text-align: center; color: var(--muted); font-size: .85rem; padding: 0 16px 28px; }
</style>
</head>
<body id="top">

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

  <?php if ($flashOk):  ?><div class="flash ok" role="status"><?= h($flashOk) ?></div><?php endif; ?>
  <?php if ($flashErr): ?><div class="flash err" role="alert"><?= h($flashErr) ?></div><?php endif; ?>

  <div class="stats">
    <div class="stat"><b><?= $totalBuildings ?></b><span>Buildings registered</span></div>
    <div class="stat"><b><?= $totalInspections ?></b><span>Inspections carried out</span></div>
    <div class="stat"><b><?= $totalViolations ?></b><span>Violations recorded</span></div>
    <div class="stat alert"><b><?= $openViolations ?></b><span>Violations still open</span></div>
  </div>

  <?php
  crud_section($conn, $E, $FK, 'building', $buildings, [
      'ID' => 'BUILDING_ID', 'Building' => 'BUILDING_NAME', 'Location' => 'ADDRESS',
      'Type' => 'BUILDING_TYPE', 'Floors' => 'FLOORS', 'Violations' => 'VIOLATIONS', 'Risk Level' => 'RISK_LEVEL'
  ], ['RISK_LEVEL']);

  crud_section($conn, $E, $FK, 'inspector', $inspectors, [
      'ID' => 'INSPECTOR_ID', 'Name' => 'INSPECTOR_NAME', 'Phone' => 'PHONE'
  ]);

  crud_section($conn, $E, $FK, 'inspection', $inspections, [
      'ID' => 'INSPECTION_ID', 'Building' => 'BUILDING_NAME', 'Inspector' => 'INSPECTOR_NAME',
      'Date' => 'INSPECTION_DATE', 'Result' => 'STATUS'
  ], ['STATUS']);

  crud_section($conn, $E, $FK, 'violation', $violations, [
      'ID' => 'VIOLATION_ID', 'Building' => 'BUILDING_NAME', 'Violation' => 'VIOLATION_TYPE',
      'Severity' => 'SEVERITY', 'Status' => 'STATUS'
  ], ['SEVERITY', 'STATUS']);

  crud_section($conn, $E, $FK, 'corrective_action', $actions, [
      'ID' => 'ACTION_ID', 'Building' => 'BUILDING_NAME', 'Violation' => 'VIOLATION_TYPE',
      'Action' => 'ACTION_DESCRIPTION', 'Status' => 'STATUS'
  ], ['STATUS']);
  ?>

  <section id="report">
    <h2>Safety report</h2>
    <p class="muted" style="margin-top:0">Read-only combined view of every inspection, its violations and the actions taken.</p>
    <?php table($report, [
        'Building' => 'BUILDING_NAME', 'Inspected' => 'INSPECTION_DATE', 'Result' => 'INSPECTION_STATUS',
        'Violation' => 'VIOLATION_TYPE', 'Severity' => 'SEVERITY', 'Violation status' => 'VIOLATION_STATUS',
        'Action' => 'ACTION_DESCRIPTION', 'Action status' => 'ACTION_STATUS'
    ], ['INSPECTION_STATUS', 'SEVERITY', 'VIOLATION_STATUS', 'ACTION_STATUS']); ?>
  </section>

</div></main>

<footer>Smart Safety Compliance Database</footer>

<?php oci_close($conn); endif; ?>
</body>
</html>