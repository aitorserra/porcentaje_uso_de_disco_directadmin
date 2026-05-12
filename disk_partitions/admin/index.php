<?php

declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderMessage(string $title, string $message, string $details = ''): void
{
    echo "<h2>Disk Partitions</h2>\n";
    echo '<div style="padding:12px;border:1px solid #d9534f;background:#fdf2f2;color:#8a1f11;margin-bottom:12px;">';
    echo '<strong>' . h($title) . '</strong><br>' . h($message);
    if ($details !== '') {
        echo '<pre style="margin-top:10px;white-space:pre-wrap;">' . h($details) . '</pre>';
    }
    echo "</div>\n";
}

function isExecAvailable(): bool
{
    if (!function_exists('exec')) {
        return false;
    }

    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return !in_array('exec', $disabled, true);
}

function formatKilobytes(int $kilobytes): string
{
    $units = ['KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    $size = (float) $kilobytes;
    $unit = 0;

    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }

    return sprintf($size >= 10 || $unit === 0 ? '%.0f %s' : '%.1f %s', $size, $units[$unit]);
}

if (!isExecAvailable()) {
    renderMessage('Runtime error', 'PHP cannot execute shell commands because exec() is unavailable.');
    exit(0);
}

$command = 'LC_ALL=C df -PT -k 2>&1';
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);

if ($exitCode !== 0 || count($output) < 2) {
    renderMessage('Command error', 'Unable to collect disk information with df.', implode("\n", $output));
    exit(0);
}

$excludedTypes = [
    'autofs',
    'binfmt_misc',
    'cgroup',
    'cgroup2',
    'configfs',
    'debugfs',
    'devpts',
    'devtmpfs',
    'fusectl',
    'hugetlbfs',
    'mqueue',
    'overlay',
    'proc',
    'pstore',
    'securityfs',
    'selinuxfs',
    'squashfs',
    'sysfs',
    'tmpfs',
    'tracefs',
];

$rows = [];
$ignoredRows = 0;

for ($i = 1, $count = count($output); $i < $count; $i++) {
    $line = trim($output[$i]);
    if ($line === '') {
        continue;
    }

    $parts = preg_split('/\s+/', $line, 7);
    if (!is_array($parts) || count($parts) < 7) {
        continue;
    }

    [$device, $type, $blocks, $used, $available, $capacity, $mount] = $parts;
    if (in_array($type, $excludedTypes, true)) {
        $ignoredRows++;
        continue;
    }

    $usedPct = max(0, min(100, (int) rtrim($capacity, '%')));
    $freePct = 100 - $usedPct;
    $rows[] = [
        'device' => $device,
        'type' => $type,
        'mount' => $mount,
        'size' => formatKilobytes((int) $blocks),
        'used' => formatKilobytes((int) $used),
        'avail' => formatKilobytes((int) $available),
        'used_pct' => $usedPct,
        'free_pct' => $freePct,
    ];
}

usort(
    $rows,
    static fn(array $left, array $right): int => [$right['used_pct'], $left['mount']] <=> [$left['used_pct'], $right['mount']]
);

?>
<h2>Disk Partitions</h2>
<?php if (empty($rows)): ?>
<div style="padding:12px;border:1px solid #d9534f;background:#fdf2f2;color:#8a1f11;">No supported filesystem entries were found.</div>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:13px;">
<thead>
<tr style="background:#2c3e50;color:#fff;">
  <th style="padding:8px 12px;text-align:left;">Device</th>
  <th style="padding:8px 12px;text-align:left;">Type</th>
  <th style="padding:8px 12px;text-align:left;">Mount</th>
  <th style="padding:8px 12px;text-align:right;">Total</th>
  <th style="padding:8px 12px;text-align:right;">Used</th>
  <th style="padding:8px 12px;text-align:right;">Free</th>
  <th style="padding:8px 12px;text-align:center;">Free %</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $k => $row): ?>
<?php
$gray = 255 - (int) round($row['free_pct'] * 2.55);
$barColor = sprintf('#%02x%02x%02x', $gray, $gray, $gray);
$rowBg = ($k % 2 === 0) ? '#f9f9f9' : '#ffffff';
?>
<tr style="background:<?php echo $rowBg; ?>;">
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($row['device']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($row['type']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($row['mount']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($row['size']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($row['used']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($row['avail']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:center;">
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
      <div style="width:80px;height:14px;background:#e8e8e8;border-radius:7px;overflow:hidden;">
        <div style="width:<?php echo $row['free_pct']; ?>%;height:100%;background:<?php echo $barColor; ?>;border-radius:7px;"></div>
      </div>
      <span style="font-size:12px;font-weight:bold;color:<?php echo $barColor; ?>;"><?php echo $row['free_pct']; ?>%</span>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
<p style="font-size:11px;color:#999;margin-top:12px;">
Generated: <?php echo h(date('Y-m-d H:i:s')); ?>
<?php if ($ignoredRows > 0): ?>
 | Filtered pseudo filesystems: <?php echo (int) $ignoredRows; ?>
<?php endif; ?>
</p>
