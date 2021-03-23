using System;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Linq;
using System.Data.SQLite;
using System.IO;

namespace VV_Viewer
{
    class Program
    {
        
        public static BookingScraper bs;
        static async Task Main(string[] args)
        {

                    Console.WriteLine(@"Please choose an area:
[1] Aquatics
[2] Fieldhouse
[3] Cardio Room
[4] Weight Room
[5] Fitness Bookings");
                string username = "";
                string dep = "";
                bool again = true;
                while (again){
                    again = false;
                    char choice = new char();
                    choice = Console.ReadKey().KeyChar;
                    Console.Clear();
                    switch (choice){
                        case '1':
                            username = "aquatics";
                            dep = "Aquatics";
                            break;
                        case '2':
                            username = "fieldhouse";
                            dep = "Fieldhouse";
                            break;
                        case '3':
                            username = "cardioroom";
                            dep = "Cardio Room";
                            break;
                        case '4':
                            username = "weightroom";
                            dep = "Weight Room";
                            break;
                        case '5':
                            username = "jsherwin";
                            dep = "Jamie Stuff";
                        break;
                        default:
                            Console.WriteLine("\nPlease enter a valid option.");
                            again = true;
                            break;
                    }
                }
                Console.WriteLine("Press D to write to database, or any other key to create html schedule");
                var dbChoice = Console.ReadKey();
                Console.Clear();
                Console.WriteLine($"Navigating to Variety Schedule Site and logging in to {username}...");
                bs = new BookingScraper();
                await bs.Load(username);
                Console.WriteLine("Successful login, navigating to current day...");
                await bs.GoToCurrentDay();
                if (dbChoice.KeyChar == 'd'){
                    string connectionString = "Data Source=/home/brady/Documents/C# Projects/VV Viewer/Databases;Version=3;";
                    using (var con = new SQLiteConnection(connectionString))
                    {
                        con.Open();
                        Console.WriteLine("Database open, retrieving bookings.");
                        bool loop = true;
                        int noBookingCounter = 0;
                        while(loop){
                            bool noBookings = !(await bs.GetBookingsOnLoadedDay());
                            if (noBookings) noBookingCounter++;
                            else noBookingCounter = 0;
                            if (noBookingCounter == 2) loop = false;
                            else {
                                if (bs.Bookings.Count > 0)
                                    Console.WriteLine($"Getting bookings for {bs.Bookings.Last().StartTime.AddDays(-1-noBookingCounter).ToString("d")}");
                                else
                                    Console.WriteLine("Getting bookings..");
                            }
                            await bs.ReturnToMainPage();
                            await bs.GoToPreviousDay();
                        }
                        Console.WriteLine("Done collecting bookings, inserting into DB.");
                            foreach (var booking in bs.Bookings){
                                string com = "INSERT INTO BOOKINGS(Name, Area, Section, DateTime) VALUES(@name, @area, @section, @date)";

                                foreach(var name in booking.Names)
                                {
                                    using (var cmd = new SQLiteCommand(com, con))
                                    {
                                        
                                        cmd.Parameters.AddWithValue("@name",name);
                                        cmd.Parameters.AddWithValue("area",dep);
                                        cmd.Parameters.AddWithValue("@section",booking.Area);
                                        cmd.Parameters.AddWithValue("@date",booking.StartTime);
                                        cmd.ExecuteNonQuery();
                                    }
                                }
                            }
                        
                        Console.WriteLine("Completed. See database for results.");
                    }
                } else {

                    Console.WriteLine("Collecting booking URLs...");
                    await bs.GetBookingsOnLoadedDay();
                    Console.WriteLine("Building schedule...");
                    string html = bs.BuildHTMLSchedule(dep);
                    File.WriteAllText($"{dep.Replace(" ","_")}_schedule.html",html);
                    Console.WriteLine($"Completed! Open {dep.Replace(" ","_")}_schedule.html to view schedule.");
                }
            }
        }
    }

