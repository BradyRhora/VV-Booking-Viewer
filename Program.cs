using System;
using System.Threading.Tasks;
using PuppeteerSharp;
using HtmlAgilityPack;
using System.Collections.Generic;
using System.Linq;
using System.IO;

namespace VV_Viewer
{
    class Program
    {
        static async Task Main(string[] args)
        {

            var options = new LaunchOptions { Headless = true, Args=new string[]{"--no-sandbox"} };
            Console.WriteLine("Downloading chromium");
            await new BrowserFetcher().DownloadAsync(BrowserFetcher.DefaultRevision);
            Console.WriteLine("Navigating to Variety Schedule Site");

            using (var browser = await Puppeteer.LaunchAsync(options))
            using (var page = await browser.NewPageAsync())
            {
                Console.WriteLine("Please choose an area:\n[1] Aquatics\n[2] Fieldhouse\n[3] Cardio Room\n[4] Weight Room");
                string username = "";
                string dep = "";
                bool again = true;
                while (again){
                    again = false;
                    var choice = Console.ReadKey();
                    switch (choice.KeyChar){
                        case '1':
                            username = "kbaker";
                            dep = "Aquatics";
                            break;
                        case '2':
                            username = "jsurdi";
                            dep = "Fieldhouse";
                            break;
                        case '3':
                            username = "chorak";
                            dep = "Cardio Room";
                            break;
                        case '4':
                            username = "ksarkar";
                            dep = "Weight Room";
                            break;
                        default:
                            Console.WriteLine("\nPlease enter a valid option.");
                            again = true;
                            break;
                    }
                }
                await page.GoToAsync("http://vvs03.corp.variety.local:8888/");
                await page.FocusAsync("body > section > form > input:nth-child(2)");
                Console.WriteLine($"\nLogging in to {dep} using {username}...");
                await page.Keyboard.TypeAsync(username);
                await page.FocusAsync("body > section > form > input:nth-child(3)");
                await page.Keyboard.TypeAsync("Summer2018");
                await page.ClickAsync("body > section > form > div > button.btn.btn-primary");
                await page.WaitForSelectorAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-right > div > button.fc-agendaDay-button.fc-button.btn");
                Console.WriteLine("Successful login, navigating to current day...");
                await page.ClickAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-right > div > button.fc-agendaDay-button.fc-button.btn");
                await page.WaitForSelectorAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-left > button");
                await page.ClickAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-left > button");
                await page.WaitForSelectorAsync("body > section > div > div.fc-view-container > div > table > tbody > tr > td > div > div > div.fc-content-skeleton > table > tbody > tr > td:nth-child(2) > div > div:nth-child(2)");
                Console.WriteLine("Collecting booking URLs...");
                HtmlDocument html = new HtmlDocument();
                string pageHTML = await page.GetContentAsync();
                html.LoadHtml(pageHTML);
                var doc = html.DocumentNode;
                var events = doc.SelectSingleNode("/html/body/section/div/div[2]/div/table/tbody/tr/td/div/div/div[3]/table/tbody/tr/td[2]/div/div[2]");
                List<string> hrefs = new List<string>();
                foreach(var node in events.ChildNodes){
                    var atts = node.Attributes;
                    hrefs.Add(atts[1].Value);
                }

                List<Booking> Bookings = new List<Booking>();
                Console.Write("[");
                int percMin = 10;
                foreach(var href in hrefs){
                    await page.GoToAsync("http://vvs03.corp.variety.local:8888" + href);
                    html.LoadHtml(await page.GetContentAsync());
                    doc = html.DocumentNode;
                    var info = doc.SelectSingleNode("/html/body/header/h1").InnerText.Split('-');
                    //Console.WriteLine(info);
                    var area = info[0].Trim();
                    var time = info[1].Trim();
                    if (time.Length == 1) time = time + ":00pm";
                    var listNode = doc.SelectSingleNode("/html/body/section/form/ul");
                    if (listNode == null) continue;
                    List<string> names = new List<string>();
                    //Console.WriteLine("Checking " + page.Url);
                    foreach(var item in listNode.ChildNodes){
                        var name = item.FirstChild.FirstChild.FirstChild.ChildNodes[1].InnerText;
                        names.Add(name);
                    }

                    Bookings.Add(new Booking(area,DateTime.Parse(time),names.ToArray()));

                    double percentage = (Convert.ToDouble(hrefs.IndexOf(href) + 1) / Convert.ToDouble(hrefs.Count)) * 100.0;
                    if  (percentage >= percMin){
                        Console.Write("|");
                        percMin+=10;
                    }
                }
                Console.WriteLine("] Done.");
                Console.WriteLine("Building schedule...");
                string htmlStart = @"<html>
    <head>
        <title>Variety Village "+dep+@" Schedule</title>
        <style>
            
            table, th,td {
                border: 1px solid black;
                border-collapse: collapse;
                font-size:20px;
                color:#f1faee;
            }
            td{
                background-color:#457b9d;
                width:120px;
            }
            th{
                background-color:#1d3557;
            }
            td.empty{
                background-color:#e63946;
            }
        
        </style>
    </head>

    <body>";
                string[] sections = Bookings.Select(x=>x.Area).Distinct().ToArray();
                string htmlMid = $"<h1>Variety Village {dep} Schedule {Bookings.First().StartTime.ToShortDateString()}";
                foreach(var section in sections){
                    var slots = Bookings.Where(x=>x.Area == section).ToArray();
                    htmlMid += $"</h1><h2>{section}</h2><table>";
                    htmlMid += "<tr>";
                    for (int i = 0; i < slots.Count(); i++){
                            var time = slots[i].StartTime.ToShortTimeString();
                            htmlMid += $"<th>{time}</th>";
                    }
                    htmlMid += "<tr>";
                    

                    for (int o = 0; o < slots.Select(x=>x.Names.Count()).Max(); o++){
                        htmlMid += "<tr>";
                        for (int i = 0; i < slots.Count(); i++){
                            if (slots[i].Names.Count() > o){
                                var name = slots[i].Names[o];
                                htmlMid += $"<td>{name}</td>";
                            } else{
                                htmlMid += $"<td class=\"empty\"></td>";
                            }
                        }
                        htmlMid += "<tr>";
                    }
                    htmlMid += "</table>";
                }
                

                string htmlEnd = "</body></html>";

                File.WriteAllText($"{dep.Replace(" ","_")}_schedule.html",htmlStart+htmlMid+htmlEnd);
                Console.WriteLine($"Completed! Open {dep.Replace(" ","_")}_schedule.html to view schedule.");
            }
        }
    }
}
