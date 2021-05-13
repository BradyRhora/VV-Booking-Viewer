using System;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Linq;
using System.Data.SQLite;
using System.IO;
using System.Net.Mail;

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
    [5] Fitness Bookings
    [6] Full DB Update");
            string username = "";
            string dep = "";
            bool again = true;
            while (again){
                again = false;
                char choice = new char();
                choice = Console.ReadKey().KeyChar;
                Console.Clear();
                //KeyValuePair[] logins;
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
            Console.WriteLine("Press D to write to database for previous days, F for future days, or any other key to create html schedule");
            var dbChoice = Console.ReadKey();
            Console.Clear();
            Console.WriteLine($"Navigating to Variety Schedule Site and logging in to {username}...");
            bs = new BookingScraper();
            await bs.Load(username);
            Console.WriteLine("Successful login, navigating to current day...");
            await bs.GoToCurrentDay();
            if (dbChoice.KeyChar == 'd' || dbChoice.KeyChar == 'f')
            {
                string connectionString = "Data Source=Databases.db;Version=3;";
                using (var con = new SQLiteConnection(connectionString))
                {
                    con.Open();
                    Console.WriteLine("Database open, retrieving bookings.");
                    bool loop = true;
                    int noBookingCounter = 0;
                    while(loop)
                    {
                        bool noBookings = !(await bs.GetBookingsOnLoadedDay());
                        if (noBookings) noBookingCounter++;
                        else noBookingCounter = 0;
                        if (noBookingCounter == 3) loop = false;
                        else 
                        {
                            if (bs.Bookings.Count > 0)
                            {
                                int dayMove = -1;
                                if (dbChoice.KeyChar == 'f') dayMove = 1;
                                Console.WriteLine($"Getting bookings for {bs.Bookings.Last().StartTime.AddDays(dayMove+(dayMove*noBookingCounter)).ToString("d")}");
                            }
                            else
                                Console.WriteLine("Getting bookings..");
                        }
                        await bs.ReturnToMainPage();
                        if (dbChoice.KeyChar == 'd') await bs.GoToPreviousDay();
                        else await bs.GoToNextDay();
                    }
                    Console.WriteLine("Done collecting bookings, clearing old bookings then inserting into DB.");

                    if(dbChoice.KeyChar=='f'){
                        string delCom = "DELETE FROM BOOKINGS WHERE strftime('%Y-%m-%d', DateTime) >= strftime('%Y-%m-%d',@date) AND Area = @area";
                        using (var cmd = new SQLiteCommand(delCom,con)){
                            cmd.Parameters.AddWithValue("@date",DateTime.Now);
                            cmd.Parameters.AddWithValue("@area",dep);
                            cmd.ExecuteNonQuery();
                        }
                    }
                    foreach (var booking in bs.Bookings)
                    {
                        string com = "INSERT INTO BOOKINGS(Name, Area, Section, DateTime) VALUES(@name, @area, @section, @date)";

                        foreach(var name in booking.Names)
                        {
                            using (var cmd = new SQLiteCommand(com, con))
                            {
                                
                                cmd.Parameters.AddWithValue("@name",name.Trim());
                                cmd.Parameters.AddWithValue("area",dep);
                                cmd.Parameters.AddWithValue("@section",booking.Area);
                                cmd.Parameters.AddWithValue("@date",booking.StartTime);
                                try{
                                    cmd.ExecuteNonQuery();
                                } catch (SQLiteException e) {
                                    Console.WriteLine($"Unable to insert into DB ({name} in {dep} at {booking.StartTime.ToShortTimeString()}\n{e.Message}");
                                }
                            }
                        }
                    }

                    string infoCom = "UPDATE INFO SET Value = @date WHERE Name = 'LastUpdate'";
                    using (var cmd = new SQLiteCommand(infoCom,con)){
                        cmd.Parameters.AddWithValue("@date",DateTime.Now);
                        cmd.ExecuteNonQuery();
                    }
                    Console.WriteLine("Completed. See database for results.");
                }
            } 
            else 
            {

                Console.WriteLine("Collecting booking URLs...");
                await bs.GetBookingsOnLoadedDay();
                Console.WriteLine("Building schedule...");
                string html = bs.BuildHTMLSchedule(dep);
                File.WriteAllText($"Schedules/{dep.Replace(" ","_")}_schedule.html",html);
                Console.WriteLine($"Completed! Open Schedules/{dep.Replace(" ","_")}_schedule.html to view schedule.");
                
                await SaveHTMLAsJPG(bs,html,dep);
                
            
            }
            
        }
    
        static async Task SaveHTMLAsJPG(BookingScraper book, string html, string dep)
        {
            File.WriteAllText($"Schedules/{dep.Replace(" ","_")}_schedule.html",html);
            Console.WriteLine($"Completed! Open Schedules/{dep.Replace(" ","_")}_schedule.html to view schedule.");
            
            var schedPage = await book.browser.NewPageAsync();
            string dir = Environment.CurrentDirectory.Replace("#","%23");
            await schedPage.GoToAsync($"file://{dir}/Schedules/{dep.Replace(" ","_")}_schedule.html");
            var ssop = new PuppeteerSharp.ScreenshotOptions();
            ssop.FullPage = true;
            await schedPage.ScreenshotAsync($"Schedules/{dep.Replace(" ","_")}_schedule.jpg",ssop);
            
        }
    }
}
