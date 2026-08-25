using System;
using System.Collections.Generic;
using System.IO;
using System.Net.Sockets;
using System.Runtime.InteropServices;
using System.Text;
using sbxpc;

namespace M50DeviceTester
{
    class Program
    {
        static int Main(string[] args)
        {
            if (args.Length == 0)
            {
                PrintUsage();
                return 0;
            }

            string command = args[0].ToLowerInvariant().TrimStart('-');
            bool jsonOutput = true; // Default to JSON for easy programmatic integration

            try
            {
                switch (command)
                {
                    case "ping":
                        return HandlePing(args);

                    case "connect":
                        return HandleConnect(args);

                    case "sysinfo":
                        return HandleSysInfo(args);

                    case "getlogs":
                        return HandleGetLogs(args);

                    case "getusers":
                        return HandleGetUsers(args);

                    case "adduser":
                        return HandleAddUser(args);

                    case "deleteuser":
                        return HandleDeleteUser(args);

                    case "unlockdoor":
                        return HandleUnlockDoor(args);

                    case "clearlogs":
                        return HandleClearLogs(args);

                    case "synctime":
                        return HandleSyncTime(args);

                    case "help":
                    default:
                        PrintUsage();
                        return 0;
                }
            }
            catch (Exception ex)
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", ex.Message },
                    { "stack", ex.StackTrace }
                });
                return 1;
            }
        }

        static void PrintUsage()
        {
            Console.WriteLine("M50 / SBXPCDLL Device Tester CLI Bridge");
            Console.WriteLine("Usage: M50DeviceTester.exe <command> [args...]");
            Console.WriteLine("Commands:");
            Console.WriteLine("  ping <ip> [port]");
            Console.WriteLine("  connect <ip> [port] [password] [machineNo]");
            Console.WriteLine("  sysinfo <ip> [port] [password] [machineNo]");
            Console.WriteLine("  getlogs <ip> [port] [password] [machineNo]");
            Console.WriteLine("  getusers <ip> [port] [password] [machineNo]");
            Console.WriteLine("  adduser <userId> <name> <cardNo> <password> <privilege> <ip> [port] [password] [machineNo]");
            Console.WriteLine("  deleteuser <userId> <ip> [port] [password] [machineNo]");
            Console.WriteLine("  unlockdoor <delaySec> <ip> [port] [password] [machineNo]");
            Console.WriteLine("  clearlogs <ip> [port] [password] [machineNo]");
            Console.WriteLine("  synctime <ip> [port] [password] [machineNo]");
        }

        static int HandlePing(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;

            var sw = System.Diagnostics.Stopwatch.StartNew();
            bool reachable = false;
            string error = null;

            try
            {
                using (var client = new TcpClient())
                {
                    var result = client.BeginConnect(ip, port, null, null);
                    bool success = result.AsyncWaitHandle.WaitOne(TimeSpan.FromSeconds(3));
                    if (success)
                    {
                        client.EndConnect(result);
                        reachable = true;
                    }
                    else
                    {
                        error = "Connection timeout";
                    }
                }
            }
            catch (Exception ex)
            {
                error = ex.Message;
            }

            sw.Stop();

            OutputJson(new Dictionary<string, object>
            {
                { "status", reachable ? "success" : "error" },
                { "ip", ip },
                { "port", port },
                { "reachable", reachable },
                { "response_time_ms", sw.ElapsedMilliseconds },
                { "error", error }
            });

            return reachable ? 0 : 1;
        }

        static int HandleConnect(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;
            int password = args.Length > 3 ? ParseIntOrDefault(args[3], 0) : 0;
            int machineNo = args.Length > 4 ? ParseIntOrDefault(args[4], 1) : 1;

            bool connected = FastConnect(machineNo, ip, port, password);

            if (connected)
            {
                SBXPCDLL.Disconnect(machineNo);
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "success" },
                    { "message", "Connected successfully via SBXPCDLL" },
                    { "ip", ip },
                    { "port", port },
                    { "machine_number", machineNo }
                });
                return 0;
            }
            else
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Failed to connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" },
                    { "ip", ip },
                    { "port", port },
                    { "machine_number", machineNo }
                });
                return 1;
            }
        }

        static int HandleSysInfo(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;
            int password = args.Length > 3 ? ParseIntOrDefault(args[3], 0) : 0;
            int machineNo = args.Length > 4 ? ParseIntOrDefault(args[4], 1) : 1;

            if (!FastConnect(machineNo, ip, port, password))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                uint userCount = 0, fpCount = 0, cardCount = 0, pwdCount = 0, glogCount = 0, slogCount = 0;
                SBXPCDLL.GetDeviceStatus(machineNo, 1, out userCount);
                SBXPCDLL.GetDeviceStatus(machineNo, 2, out fpCount);
                SBXPCDLL.GetDeviceStatus(machineNo, 3, out pwdCount);
                SBXPCDLL.GetDeviceStatus(machineNo, 4, out cardCount);
                SBXPCDLL.GetDeviceStatus(machineNo, 6, out glogCount);
                SBXPCDLL.GetDeviceStatus(machineNo, 7, out slogCount);

                string serialNo = "";
                SBXPCDLL.GetSerialNumber(machineNo, out serialNo);

                string productCode = "";
                SBXPCDLL.GetProductCode(machineNo, out productCode);

                OutputJson(new Dictionary<string, object>
                {
                    { "status", "success" },
                    { "serial_number", serialNo },
                    { "product_code", productCode },
                    { "user_count", userCount },
                    { "fingerprint_count", fpCount },
                    { "card_count", cardCount },
                    { "password_count", pwdCount },
                    { "attendance_log_count", glogCount },
                    { "super_log_count", slogCount }
                });
                return 0;
            }
            finally
            {
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleGetLogs(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;
            int password = args.Length > 3 ? ParseIntOrDefault(args[3], 0) : 0;
            int machineNo = args.Length > 4 ? ParseIntOrDefault(args[4], 1) : 1;

            if (!FastConnect(machineNo, ip, port, password))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                SBXPCDLL.EnableDevice(machineNo, 0); // Disable input while reading

                bool ok = SBXPCDLL.ReadAllGLogData(machineNo);
                if (!ok)
                {
                    int errCode = 0;
                    SBXPCDLL.GetLastError(machineNo, out errCode);
                    OutputJson(new Dictionary<string, object>
                    {
                        { "status", "error" },
                        { "message", "ReadAllGLogData failed. Error code: " + errCode }
                    });
                    return 1;
                }

                var logs = new List<Dictionary<string, object>>();

                while (true)
                {
                    int tmno, seno, smno, vmode, yr, mon, day, hr, min, sec;
                    bool vRet = SBXPCDLL.GetAllGLogData(machineNo, out tmno, out seno, out smno, out vmode, out yr, out mon, out day, out hr, out min, out sec);
                    if (!vRet) break;

                    string sUserId = "";
                    try
                    {
                        SBXPCDLL.GetLastBigUserId_AsString1(machineNo, out sUserId, false);
                    }
                    catch { }

                    bool isValidBigId = !string.IsNullOrWhiteSpace(sUserId) && 
                                        sUserId.Trim() != "0" && 
                                        sUserId.Trim() != "00000000" && 
                                        sUserId.Trim() != "00";

                    string finalUserId = isValidBigId ? sUserId.Trim() : seno.ToString();
                    string timestamp = string.Format("{0:D4}-{1:D2}-{2:D2} {3:D2}:{4:D2}:{5:D2}", yr, mon, day, hr, min, sec);

                    logs.Add(new Dictionary<string, object>
                    {
                        { "device_id", machineNo },
                        { "user_id", finalUserId },
                        { "raw_enroll_no", seno },
                        { "timestamp", timestamp },
                        { "verify_type", vmode },
                        { "sensor_no", smno }
                    });
                }

                OutputJson(new Dictionary<string, object>
                {
                    { "status", "success" },
                    { "count", logs.Count },
                    { "logs", logs }
                });
                return 0;
            }
            finally
            {
                SBXPCDLL.EnableDevice(machineNo, 1);
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleGetUsers(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;
            int password = args.Length > 3 ? ParseIntOrDefault(args[3], 0) : 0;
            int machineNo = args.Length > 4 ? ParseIntOrDefault(args[4], 1) : 1;

            if (!FastConnect(machineNo, ip, port, password))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                SBXPCDLL.EnableDevice(machineNo, 0);

                bool ok = SBXPCDLL.ReadAllUserID(machineNo);
                if (!ok)
                {
                    OutputJson(new Dictionary<string, object>
                    {
                        { "status", "error" },
                        { "message", "ReadAllUserID failed" }
                    });
                    return 1;
                }

                var users = new List<Dictionary<string, object>>();

                while (true)
                {
                    int enrollNo, eMachineNo, backupNo, privilege, enable;
                    bool vRet = SBXPCDLL.GetAllUserID(machineNo, out enrollNo, out eMachineNo, out backupNo, out privilege, out enable);
                    if (!vRet) break;

                    string userName = "";
                    try
                    {
                        SBXPCDLL.GetUserName1(machineNo, enrollNo, out userName);
                    }
                    catch { }

                    users.Add(new Dictionary<string, object>
                    {
                        { "user_id", enrollNo },
                        { "machine_no", eMachineNo },
                        { "backup_no", backupNo },
                        { "privilege", privilege },
                        { "enabled", enable },
                        { "name", userName }
                    });
                }

                OutputJson(new Dictionary<string, object>
                {
                    { "status", "success" },
                    { "count", users.Count },
                    { "users", users }
                });
                return 0;
            }
            finally
            {
                SBXPCDLL.EnableDevice(machineNo, 1);
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleAddUser(string[] args)
        {
            // adduser <userId> <name> <cardNo> <password> <privilege> <ip> [port] [password] [machineNo]
            if (args.Length < 7)
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Missing arguments. Usage: adduser <userId> <name> <cardNo> <password> <privilege> <ip> [port] [pass] [mNo]" }
                });
                return 1;
            }

            int userId = ParseIntOrDefault(args[1], 1);
            string name = args[2];
            int cardNo = ParseIntOrDefault(args[3], 0);
            int userPassword = ParseIntOrDefault(args[4], 1234);
            int privilege = ParseIntOrDefault(args[5], 0); // 0: User, 1: Admin
            string ip = args[6];
            int port = args.Length > 7 ? ParseIntOrDefault(args[7], 5005) : 5005;
            int commPassword = args.Length > 8 ? ParseIntOrDefault(args[8], 0) : 0;
            int machineNo = args.Length > 9 ? ParseIntOrDefault(args[9], 1) : 1;

            if (!FastConnect(machineNo, ip, port, commPassword))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                SBXPCDLL.EnableDevice(machineNo, 0);

                // 1. Create user record on device using SetEnrollData1 (Password backup number 15)
                byte[] dummyData = new byte[10];
                GCHandle gh = GCHandle.Alloc(dummyData, GCHandleType.Pinned);
                IntPtr addr = gh.AddrOfPinnedObject();

                bool pwdOk = SBXPCDLL.SetEnrollData1(machineNo, userId, 15, privilege, addr, userPassword);

                // 2. Set RFID Card Number if provided (Backup number 11)
                bool cardOk = true;
                if (cardNo > 0)
                {
                    cardOk = SBXPCDLL.SetEnrollData1(machineNo, userId, 11, privilege, addr, cardNo);
                }

                // 3. Set User Name on device using SetUserName1
                bool nameOk = true;
                if (!string.IsNullOrEmpty(name))
                {
                    nameOk = SBXPCDLL.SetUserName1(machineNo, userId, name);
                }

                gh.Free();

                OutputJson(new Dictionary<string, object>
                {
                    { "status", (pwdOk && nameOk) ? "success" : "error" },
                    { "user_id", userId },
                    { "name", name },
                    { "password_set", pwdOk },
                    { "card_set", cardOk },
                    { "name_set", nameOk }
                });
                return (pwdOk && nameOk) ? 0 : 1;
            }
            finally
            {
                SBXPCDLL.EnableDevice(machineNo, 1);
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleDeleteUser(string[] args)
        {
            if (args.Length < 3)
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Missing arguments. Usage: deleteuser <userId> <ip> [port] [password] [machineNo]" }
                });
                return 1;
            }

            int userId = ParseIntOrDefault(args[1], 1);
            string ip = args[2];
            int port = args.Length > 3 ? ParseIntOrDefault(args[3], 5005) : 5005;
            int commPassword = args.Length > 4 ? ParseIntOrDefault(args[4], 0) : 0;
            int machineNo = args.Length > 5 ? ParseIntOrDefault(args[5], 1) : 1;

            if (!FastConnect(machineNo, ip, port, commPassword))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                SBXPCDLL.EnableDevice(machineNo, 0);
                // dwBackupNumber = 12 deletes user completely
                bool deleted = SBXPCDLL.DeleteEnrollData(machineNo, userId, machineNo, 12);

                OutputJson(new Dictionary<string, object>
                {
                    { "status", deleted ? "success" : "error" },
                    { "user_id", userId },
                    { "deleted", deleted }
                });
                return deleted ? 0 : 1;
            }
            finally
            {
                SBXPCDLL.EnableDevice(machineNo, 1);
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleUnlockDoor(string[] args)
        {
            int delaySec = args.Length > 1 ? ParseIntOrDefault(args[1], 5) : 5;
            string ip = args.Length > 2 ? args[2] : "192.168.1.201";
            int port = args.Length > 3 ? ParseIntOrDefault(args[3], 5005) : 5005;
            int commPassword = args.Length > 4 ? ParseIntOrDefault(args[4], 0) : 0;
            int machineNo = args.Length > 5 ? ParseIntOrDefault(args[5], 1) : 1;

            if (!FastConnect(machineNo, ip, port, commPassword))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                // Status 3: Open door signal
                bool opened = SBXPCDLL.SetDoorStatus(machineNo, 3);
                OutputJson(new Dictionary<string, object>
                {
                    { "status", opened ? "success" : "error" },
                    { "door_unlocked", opened },
                    { "delay_seconds", delaySec }
                });
                return opened ? 0 : 1;
            }
            finally
            {
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleClearLogs(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;
            int commPassword = args.Length > 3 ? ParseIntOrDefault(args[3], 0) : 0;
            int machineNo = args.Length > 4 ? ParseIntOrDefault(args[4], 1) : 1;

            if (!FastConnect(machineNo, ip, port, commPassword))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                SBXPCDLL.EnableDevice(machineNo, 0);
                bool cleared = SBXPCDLL.EmptyGeneralLogData(machineNo);

                OutputJson(new Dictionary<string, object>
                {
                    { "status", cleared ? "success" : "error" },
                    { "logs_cleared", cleared }
                });
                return cleared ? 0 : 1;
            }
            finally
            {
                SBXPCDLL.EnableDevice(machineNo, 1);
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int HandleSyncTime(string[] args)
        {
            string ip = args.Length > 1 ? args[1] : "192.168.1.201";
            int port = args.Length > 2 ? ParseIntOrDefault(args[2], 5005) : 5005;
            int commPassword = args.Length > 3 ? ParseIntOrDefault(args[3], 0) : 0;
            int machineNo = args.Length > 4 ? ParseIntOrDefault(args[4], 1) : 1;

            if (!FastConnect(machineNo, ip, port, commPassword))
            {
                OutputJson(new Dictionary<string, object>
                {
                    { "status", "error" },
                    { "message", "Cannot connect to device at " + ip + ":" + port + " (Unreachable or connection timed out)" }
                });
                return 1;
            }

            try
            {
                bool synced = SBXPCDLL.SetDeviceTime(machineNo);

                OutputJson(new Dictionary<string, object>
                {
                    { "status", synced ? "success" : "error" },
                    { "time_synced", synced },
                    { "system_time", DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") }
                });
                return synced ? 0 : 1;
            }
            finally
            {
                SBXPCDLL.Disconnect(machineNo);
            }
        }

        static int ParseIntOrDefault(string input, int defVal)
        {
            if (string.IsNullOrEmpty(input)) return defVal;
            int res;
            if (int.TryParse(input, out res)) return res;
            return defVal;
        }

        static void OutputJson(object data)
        {
            Console.WriteLine(ToJson(data));
        }

        static string ToJson(object obj)
        {
            if (obj == null) return "null";
            if (obj is string) return "\"" + EscapeString((string)obj) + "\"";
            if (obj is bool) return (bool)obj ? "true" : "false";
            if (obj is int || obj is long || obj is double || obj is float) return obj.ToString();

            if (obj is IDictionary<string, object>)
            {
                var dict = (IDictionary<string, object>)obj;
                var sb = new StringBuilder();
                sb.Append("{");
                bool first = true;
                foreach (var kvp in dict)
                {
                    if (!first) sb.Append(",");
                    sb.Append("\"").Append(EscapeString(kvp.Key)).Append("\":").Append(ToJson(kvp.Value));
                    first = false;
                }
                sb.Append("}");
                return sb.ToString();
            }

            if (obj is System.Collections.IEnumerable)
            {
                var sb = new StringBuilder();
                sb.Append("[");
                bool first = true;
                foreach (var item in (System.Collections.IEnumerable)obj)
                {
                    if (!first) sb.Append(",");
                    sb.Append(ToJson(item));
                    first = false;
                }
                sb.Append("]");
                return sb.ToString();
            }

            return "\"" + EscapeString(obj.ToString()) + "\"";
        }

        static string EscapeString(string s)
        {
            if (string.IsNullOrEmpty(s)) return "";
            return s.Replace("\\", "\\\\").Replace("\"", "\\\"").Replace("\r", "\\r").Replace("\n", "\\n");
        }

        static bool FastConnect(int machineNo, string ip, int port, int password, int timeoutSeconds = 2)
        {
            try
            {
                using (var client = new TcpClient())
                {
                    var asyncResult = client.BeginConnect(ip, port, null, null);
                    bool success = asyncResult.AsyncWaitHandle.WaitOne(TimeSpan.FromSeconds(timeoutSeconds));
                    if (!success)
                    {
                        return false;
                    }
                    client.EndConnect(asyncResult);
                }
            }
            catch
            {
                return false;
            }

            SBXPCDLL.DotNET();
            return SBXPCDLL.ConnectTcpip(machineNo, ip, port, password);
        }
    }
}
