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

function formatBytes(int $bytes): string
{
    $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    $size = (float) max(0, $bytes);
    $unit = 0;

    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }

    return sprintf($size >= 10 || $unit === 0 ? '%.0f %s' : '%.1f %s', $size, $units[$unit]);
}

function requestQueryParams(): array
{
    if (!empty($_GET) && is_array($_GET)) {
        return $_GET;
    }

    $queryString = $_SERVER['QUERY_STRING'] ?? getenv('QUERY_STRING') ?: '';
    if (!is_string($queryString) || $queryString === '') {
        return [];
    }

    parse_str($queryString, $params);
    return is_array($params) ? $params : [];
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
        continue;
    }

    $usedPct = max(0, min(100, (int) rtrim($capacity, '%')));
    $rows[] = [
        'device' => $device,
        'type' => $type,
        'mount' => $mount,
        'size' => formatKilobytes((int) $blocks),
        'used' => formatKilobytes((int) $used),
        'avail' => formatKilobytes((int) $available),
        'used_pct' => $usedPct,
    ];
}

usort(
    $rows,
    static fn(array $left, array $right): int => [$right['used_pct'], $left['mount']] <=> [$left['used_pct'], $right['mount']]
);

$queryParams = requestQueryParams();
$mountOptions = array_column($rows, 'mount');
$selectedMount = isset($queryParams['mount']) ? trim((string) $queryParams['mount']) : '';
$selectedMount = in_array($selectedMount, $mountOptions, true) ? $selectedMount : '';

$directoryEntries = [];
$directoryError = '';
$directoryEntryLimit = 200;

if ($selectedMount !== '') {
    if (!is_dir($selectedMount)) {
        $directoryError = 'The selected mount point is not a directory.';
    } elseif (!is_readable($selectedMount)) {
        $directoryError = 'The selected mount point is not readable by DirectAdmin.';
    } else {
        $entries = @scandir($selectedMount);
        if ($entries === false) {
            $directoryError = 'Unable to list the selected mount point.';
        } else {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = rtrim($selectedMount, '/');
                $path = ($path === '' ? '/' : $path) . '/' . $entry;
                $isDir = is_dir($path);

                $directoryEntries[] = [
                    'name' => $entry,
                    'path' => $path,
                    'type' => $isDir ? 'Directory' : 'File',
                    'size' => $isDir ? '-' : formatBytes((int) @filesize($path)),
                ];

                if (count($directoryEntries) >= $directoryEntryLimit) {
                    break;
                }
            }

            usort(
                $directoryEntries,
                static function (array $left, array $right): int {
                    if ($left['type'] !== $right['type']) {
                        return $left['type'] <=> $right['type'];
                    }

                    return strcasecmp($left['name'], $right['name']);
                }
            );
        }
    }
}

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
  <th style="padding:8px 12px;text-align:center;">Used %</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $k => $row): ?>
<?php
$gray = 255 - (int) round($row['used_pct'] * 2.55);
$barColor = sprintf('#%02x%02x%02x', $gray, $gray, $gray);
$rowBg = ($k % 2 === 0) ? '#f9f9f9' : '#ffffff';
?>
<tr style="background:<?php echo $rowBg; ?>;">
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($row['device']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($row['type']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;">
    <a href="?mount=<?php echo rawurlencode($row['mount']); ?>" style="color:#1f4f82;text-decoration:none;font-weight:600;">
      <?php echo h($row['mount']); ?>
    </a>
  </td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($row['size']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($row['used']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($row['avail']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:center;">
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
      <div style="width:80px;height:14px;background:#e8e8e8;overflow:hidden;">
        <div style="width:<?php echo $row['used_pct']; ?>%;height:100%;background:<?php echo $barColor; ?>;"></div>
      </div>
      <span style="font-size:12px;font-weight:bold;color:#333;"><?php echo $row['used_pct']; ?>%</span>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
<?php if ($selectedMount !== ''): ?>
<h3 style="margin-top:18px;">Contents of <?php echo h($selectedMount); ?></h3>
<?php if ($directoryError !== ''): ?>
<div style="padding:12px;border:1px solid #d9534f;background:#fdf2f2;color:#8a1f11;"><?php echo h($directoryError); ?></div>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:13px;margin-top:8px;">
<thead>
<tr style="background:#5d6d7e;color:#fff;">
  <th style="padding:8px 12px;text-align:left;">Name</th>
  <th style="padding:8px 12px;text-align:left;">Type</th>
  <th style="padding:8px 12px;text-align:left;">Path</th>
  <th style="padding:8px 12px;text-align:right;">Size</th>
</tr>
</thead>
<tbody>
<?php if (empty($directoryEntries)): ?>
<tr>
  <td colspan="4" style="padding:10px 12px;border-bottom:1px solid #eee;color:#666;">No visible entries found.</td>
</tr>
<?php else: ?>
<?php foreach ($directoryEntries as $index => $entry): ?>
<?php $entryBg = ($index % 2 === 0) ? '#f9f9f9' : '#ffffff'; ?>
<tr style="background:<?php echo $entryBg; ?>;">
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($entry['name']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($entry['type']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;"><?php echo h($entry['path']); ?></td>
  <td style="padding:6px 12px;border-bottom:1px solid #eee;text-align:right;"><?php echo h($entry['size']); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<?php if (count($directoryEntries) >= $directoryEntryLimit): ?>
<p style="font-size:11px;color:#999;margin-top:8px;">Showing the first <?php echo (int) $directoryEntryLimit; ?> entries.</p>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<p style="font-size:11px;color:#999;margin-top:12px;">by aitorserra</p>
