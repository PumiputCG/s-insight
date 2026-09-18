<#
  bplus_pull.ps1 — ดึงข้อมูลพนักงานทุกคอลัมน์จาก Bplus (SQL Server) ออกเป็นไฟล์ JSON
  ใช้ System.Data.SqlClient (ไม่ต้องลง driver) — เครื่อง dev ต่อ LAN ตรงได้

  ตัวอย่าง:
    powershell -ExecutionPolicy Bypass -File scripts\bplus_pull.ps1 `
      -Server 192.168.5.7 -Port 1433 -Database SUPAVUT_INDUSTRY `
      -User <readonly_user> -Password <password> `
      -Out storage\app\bplus\SUPAVUT_INDUSTRY.json

  หมายเหตุ:
   - ห้าม commit ไฟล์ JSON ที่มีเลขบัตร/ข้อมูลส่วนตัว และห้ามฮาร์ดโค้ดรหัสผ่านในไฟล์ใด ๆ
   - ปรับ -Query ได้ ถ้าหัวหน้าขยาย view ให้มีคอลัมน์ วันเริ่มงาน/ลาออก/สถานะ/เบอร์โทร เพิ่ม
#>
param(
  [string]$Server   = "192.168.5.7",
  [string]$Port     = "1433",
  [Parameter(Mandatory = $true)][string]$Database,
  [Parameter(Mandatory = $true)][string]$User,
  [Parameter(Mandatory = $true)][string]$Password,
  [string]$Query    = "SELECT * FROM dbo.EMP_MAIN",
  [Parameter(Mandatory = $true)][string]$Out,
  [int]$Timeout     = 30
)

$ErrorActionPreference = 'Stop'

$cs = "Server=$Server,$Port;Database=$Database;User Id=$User;Password=$Password;TrustServerCertificate=True;Connect Timeout=$Timeout"

$conn = New-Object System.Data.SqlClient.SqlConnection $cs
$table = New-Object System.Data.DataTable
try {
  $conn.Open()
  $cmd = $conn.CreateCommand()
  $cmd.CommandText = $Query
  $cmd.CommandTimeout = 120
  $adapter = New-Object System.Data.SqlClient.SqlDataAdapter $cmd
  [void]$adapter.Fill($table)
} finally {
  $conn.Close()
}

Write-Host ("ดึงได้ {0} แถว, {1} คอลัมน์ จาก {2}" -f $table.Rows.Count, $table.Columns.Count, $Database)
Write-Host ("คอลัมน์: " + (($table.Columns | ForEach-Object { $_.ColumnName }) -join ", "))

# แปลงเป็น array ของ object (trim ค่า string)
$rows = New-Object System.Collections.ArrayList
foreach ($r in $table.Rows) {
  $obj = [ordered]@{}
  foreach ($c in $table.Columns) {
    $v = $r[$c.ColumnName]
    if ($v -is [System.DBNull]) { $v = $null }
    elseif ($v -is [string])    { $v = $v.Trim() }
    elseif ($v -is [datetime])  { $v = $v.ToString("yyyy-MM-dd") }
    $obj[$c.ColumnName] = $v
  }
  [void]$rows.Add($obj)
}

# สร้างโฟลเดอร์ปลายทางถ้ายังไม่มี
$dir = Split-Path -Parent $Out
if ($dir -and -not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }

# ครอบให้เป็น JSON array เสมอ: ว่าง -> [] ; 1 แถว -> [ {...} ] (กัน ConvertTo-Json คืน null/object เดี่ยว)
if ($rows.Count -eq 0) {
  $json = '[]'
} elseif ($rows.Count -eq 1) {
  $json = '[' + ($rows[0] | ConvertTo-Json -Depth 5) + ']'
} else {
  $json = $rows | ConvertTo-Json -Depth 5
}
[System.IO.File]::WriteAllText($Out, $json, (New-Object System.Text.UTF8Encoding($false)))
Write-Host ("เขียนไฟล์: {0}" -f $Out)
