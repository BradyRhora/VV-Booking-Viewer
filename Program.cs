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
                    
                    
                    List<DateTime> slots = new List<DateTime>();
                    string com = "select distinct datetime from bookings where strftime('%Y-%m-%d', datetime) = strftime('%Y-%m-%d',@date) and area = @area order by datetime";
                    using (var cmd = new SQLiteCommand(com, con))
                    {
                        cmd.Parameters.AddWithValue("@date",buildDay);
                        cmd.Parameters.AddWithValue("@area",dep);
                        using (var reader = await cmd.ExecuteReaderAsync())
                        {
                            while (await reader.ReadAsync())
                            {
                                slots.Add(reader.GetDateTime(0));
                            }
                        }
                    }
                    List<Booking> bookingsToAdd = new List<Booking>();
                    foreach (var slot in slots)
                    {
                        com = "select name, section from bookings where datetime = @date and area = @area";
                        //Console.WriteLine($"test... on slot: {slot.ToShortTimeString()}");
                        using (var cmd = new SQLiteCommand(com, con))
                        {
                            cmd.Parameters.AddWithValue("@date",slot);
                            cmd.Parameters.AddWithValue("@area",dep);
                            using (var reader = await cmd.ExecuteReaderAsync())
                            {
                                while (await reader.ReadAsync())
                                {
                                    string name = reader.GetString(0);
                                    string section = reader.GetString(1);
                                    if (bookingsToAdd.Where(x=>x.Area == section && x.StartTime == slot).Count() > 0){
                                        bookingsToAdd.Where(x=>x.Area==section && x.StartTime == slot).First().AddName(name);
                                    } else {
                                        bookingsToAdd.Add(new Booking(section,slot,new string[] {name}));
                                    }
                                }
                            }
                        }
                    }
                    BookingScraper bookingScraper = new BookingScraper();
                    await bookingScraper.SoftLoad();
                    bookingScraper.InsertBookings(bookingsToAdd);
                    var html = bookingScraper.BuildHTMLSchedule(dep);
                    await SaveHTMLAsJPG(bookingScraper, html, dep);
                    MailJPG(dep,bookingScraper);
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

                        string infoCom = "UPDATE INFO SET lastupdate = @date";
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
                    
                    Console.WriteLine("Would you like to send an email with the schedule to the VVScheduleMail account? (y/n)");
                    var mailChoice = Console.ReadKey();
                    if (mailChoice.KeyChar=='y')
                    {
                        Console.WriteLine();
                        MailJPG(dep, bs);
                    }
                
                }
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
    
        static void MailJPG(string dep,BookingScraper book)
        {
            Console.Write("Sending email... ");
            MailMessage mail = new MailMessage();
            SmtpClient SmtpServer = new SmtpClient("smtp.gmail.com");
            if (!File.Exists("mailCreds")){
                var mc = File.Create("mailCreds");
                mc.Close();
                Console.WriteLine("Mail credentials file not found... Please log in:");
                Console.Write("Email: ");
                var user = Console.ReadLine();
                Console.Write("Password: ");
                var pass = Console.ReadLine();
                File.WriteAllLines("mailCreds", new string[]{user,pass});
            }
            string[] credentials = File.ReadAllLines("mailCreds");
            mail.From = new MailAddress(credentials[0]);
            mail.To.Add(credentials[0]);
            mail.Subject = $"{dep} - {book.Bookings.First().StartTime.ToString("MMMM d")} Schedule";
            mail.Body = "File attached.";

            System.Net.Mail.Attachment attachment;
            attachment = new System.Net.Mail.Attachment($"Schedules/{dep.Replace(" ","_")}_schedule.jpg");
            mail.Attachments.Add(attachment);

            SmtpServer.Port = 587;
            SmtpServer.Credentials = new System.Net.NetworkCredential(credentials[0], credentials[1]);
            SmtpServer.EnableSsl = true;

            SmtpServer.Send(mail);
            Console.WriteLine("Email sent!");
        }
    }
}
