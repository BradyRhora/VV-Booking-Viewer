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
            if (args.Count() > 0 && args[0] == "prep")
            {
                Console.WriteLine(@"Please choose an area for prep:
    [1] Aquatics
    [2] Fieldhouse
    [3] Cardio Room
    [4] Weight Room
    [5] Fitness Bookings");
                string dep = "";
                bool again = true;
                while (again){
                    again = false;
                    char choice = new char();
                    choice = Console.ReadKey().KeyChar;
                    Console.Clear();
                    switch (choice){
                        case '1':
                            dep = "Aquatics";
                            break;
                        case '2':
                            dep = "Fieldhouse";
                            break;
                        case '3':
                            dep = "Cardio Room";
                            break;
                        case '4':
                            dep = "Weight Room";
                            break;
                        break;
                        default:
                            Console.WriteLine("\nPlease enter a valid option.");
                            again = true;
                            break;
                    }
                }


                var buildDay = new DateTime(0);
                if (DateTime.Now.Hour < 6) //today
                    buildDay = DateTime.Now;
                else //tmrw
                    buildDay = DateTime.Now + new TimeSpan(24,0,0);

                string connectionString = "Data Source=Databases.db;Version=3;";
                using (var con = new SQLiteConnection(connectionString))
                {
                    con.Open();
                    Console.WriteLine("Database open, retrieving bookings.");
                    
                    foreach (var booking in bs.Bookings)
                    {
                        string com = "select distinct datetime, area from bookings where strftime('%Y-%m-%d', datetime) = strftime('%Y-%m-%d',@date) and area = @area order by datetime";

                        using (var cmd = new SQLiteCommand(com, con))
                        {
                            cmd.Parameters.AddWithValue("@date",buildDay);
                            cmd.Parameters.AddWithValue("@date",dep);
                            using (var reader = await cmd.ExecuteReaderAsync())
                            {
                                List<Booking> bookings = new List<Booking>();
                                while (await reader.ReadAsync())
                                {
                                    //continue here, this returns just the date and times, use those to get names and maybe store in KeyValuePair<Time,Name[]> or something
                                }
                            }
                        }
                    }
                }
            }
            else
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
                            if (noBookingCounter == 2) loop = false;
                            else 
                            {
                                if (bs.Bookings.Count > 0)
                                    Console.WriteLine($"Getting bookings for {bs.Bookings.Last().StartTime.AddDays(-1-noBookingCounter).ToString("d")}");
                                else
                                    Console.WriteLine("Getting bookings..");
                            }
                            await bs.ReturnToMainPage();
                            if (dbChoice.KeyChar == 'd') await bs.GoToPreviousDay();
                            else await bs.GoToNextDay();
                        }
                        Console.WriteLine("Done collecting bookings, inserting into DB.");
                        foreach (var booking in bs.Bookings)
                        {
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
                } 
                else 
                {

                    Console.WriteLine("Collecting booking URLs...");
                    await bs.GetBookingsOnLoadedDay();
                    Console.WriteLine("Building schedule...");
                    string html = bs.BuildHTMLSchedule(dep);
                    File.WriteAllText($"{dep.Replace(" ","_")}_schedule.html",html);
                    Console.WriteLine($"Completed! Open {dep.Replace(" ","_")}_schedule.html to view schedule.");
                    
                    var schedPage = await bs.browser.NewPageAsync();
                    string dir = Environment.CurrentDirectory.Replace("#","%23");
                    await schedPage.GoToAsync($"file://{dir}/{dep.Replace(" ","_")}_schedule.html");
                    var ssop = new PuppeteerSharp.ScreenshotOptions();
                    ssop.FullPage = true;
                    await schedPage.ScreenshotAsync($"{dep.Replace(" ","_")}_schedule.jpg",ssop);
                    
                    Console.WriteLine("Would you like to send an email with the schedule to the VVScheduleMail account? (y/n)");
                    var mailChoice = Console.ReadKey();
                    if (mailChoice.KeyChar=='y')
                    {
                        MailMessage mail = new MailMessage();
                        SmtpClient SmtpServer = new SmtpClient("smtp.gmail.com");
                        string[] credentials = File.ReadAllLines("mailCreds");
                        mail.From = new MailAddress(credentials[0]);
                        mail.To.Add(credentials[0]);
                        mail.Subject = $"Variety Village {dep} Schedule {DateTime.Now.ToString("m")}";
                        mail.Body = "File attached.";

                        System.Net.Mail.Attachment attachment;
                        attachment = new System.Net.Mail.Attachment($"{dep.Replace(" ","_")}_schedule.jpg");
                        mail.Attachments.Add(attachment);

                        SmtpServer.Port = 587;
                        SmtpServer.Credentials = new System.Net.NetworkCredential(credentials[0], credentials[1]);
                        SmtpServer.EnableSsl = true;

                        SmtpServer.Send(mail);
                        Console.WriteLine("Email sent!");
                    }
                
                }
            }
        }
    }
}
