' run-schedule-hidden.vbs
' Runs run-schedule.cmd with NO visible window. Called by Task Scheduler every 1 minute.
' Chain: Task "InsightBplusSync" -> this VBS -> run-schedule.cmd -> php artisan schedule:run
' (Laravel then runs bplus:sync every 15 minutes per routes/console.php)
Option Explicit
Dim sh, here
Set sh = CreateObject("WScript.Shell")
here = Left(WScript.ScriptFullName, InStrRev(WScript.ScriptFullName, "\"))
sh.Run """" & here & "run-schedule.cmd""", 0, False
