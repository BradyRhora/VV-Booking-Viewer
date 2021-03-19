using System;
using PuppeteerSharp;
using HtmlAgilityPack;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Linq;

namespace VV_Viewer
{
    class BookingScraper
    {
        private static string mainPage = "http://vvs03.corp.variety.local:8888";
        Browser browser;
        Page page;
        public List<Booking> Bookings {get; private set;}
        public async Task Load(string username)
        {
            Bookings = new List<Booking>();
            var options = new LaunchOptions { Headless = true, Args=new string[]{"--no-sandbox"} };
            //Console.WriteLine("Downloading chromium");
            await new BrowserFetcher().DownloadAsync(BrowserFetcher.DefaultRevision);
            browser = await Puppeteer.LaunchAsync(options);
            page = await browser.NewPageAsync();
            
            await page.GoToAsync(mainPage);
            await page.FocusAsync("body > section > form > input:nth-child(2)");
            await page.Keyboard.TypeAsync(username);
            await page.FocusAsync("body > section > form > input:nth-child(3)");
            await page.Keyboard.TypeAsync("Variety2020");
            await page.ClickAsync("body > section > form > div > button.btn.btn-primary");
            await page.WaitForSelectorAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-right > div > button.fc-agendaDay-button.fc-button.btn");
            
        }

        public async Task ReturnToMainPage(){
            await page.GoToAsync(mainPage);
            await page.WaitForSelectorAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-right > div > button.fc-agendaDay-button.fc-button.btn");
            
        }

        public async Task GoToCurrentDay() //(from main page)
        {
            await page.ClickAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-right > div > button.fc-agendaDay-button.fc-button.btn");
            await page.WaitForSelectorAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-left > button");
            await page.ClickAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-left > button");
            await page.WaitForSelectorAsync("body > section > div > div.fc-view-container > div > table > tbody > tr > td > div > div > div.fc-content-skeleton > table > tbody > tr > td:nth-child(2) > div > div:nth-child(2)");
            
        }

        public async Task GoToPreviousDay()
        {
            await page.ClickAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-left > div > button.fc-prev-button.fc-button.fc-corner-left.btn");
            await page.WaitForSelectorAsync("body > section > div > div.fc-view-container > div > table > tbody > tr > td > div > div > div.fc-content-skeleton > table > tbody > tr > td:nth-child(2) > div > div:nth-child(2)");
        }

        public async Task GoToNextDay()
        {
            await page.ClickAsync("body > section > div > div.fc-toolbar.fc-header-toolbar > div.fc-left > div > button.fc-next-button.fc-button.fc-corner-right.btn");
            await page.WaitForSelectorAsync("body > section > div > div.fc-view-container > div > table > tbody > tr > td > div > div > div.fc-content-skeleton > table > tbody > tr > td:nth-child(2) > div > div:nth-child(2)");
        }

        public async Task<bool> GetBookingsOnLoadedDay()
        {
            HtmlDocument html = new HtmlDocument();
            string pageHTML = await page.GetContentAsync();
            html.LoadHtml(pageHTML);
            var doc = html.DocumentNode;
            var events = doc.SelectSingleNode("/html/body/section/div/div[2]/div/table/tbody/tr/td/div/div/div[3]/table/tbody/tr/td[2]/div/div[2]");
            if (events.ChildNodes.Count() == 0){
                //Console.WriteLine("Unable to find any bookings for today! If you're sure they're there, try running again.");
                return false;
            }
            List<string> hrefs = new List<string>();
            foreach(var node in events.ChildNodes){
                var atts = node.Attributes;
                hrefs.Add(atts[1].Value);
            }

            Console.Write("[");
            int percMin = 10;
            foreach(var href in hrefs){
                await page.GoToAsync(mainPage + href);
                html.LoadHtml(await page.GetContentAsync());
                doc = html.DocumentNode;
                var area = doc.SelectSingleNode("/html/body/header/h1").InnerText.Split('-')[0].Trim();
                //Console.WriteLine(info);
                var time = doc.SelectSingleNode("/html/body/header/h5").InnerText.Replace("Class Time:","").Replace("GMT-0400 (Eastern Daylight Time)","").Trim();
                var dTime = DateTime.Parse(time);
                
                var listNode = doc.SelectSingleNode("/html/body/section/form/ul");
                if (listNode == null) continue;
                List<string> names = new List<string>();
                //Console.WriteLine("Checking " + page.Url);
                foreach(var item in listNode.ChildNodes){
                    var name = item.FirstChild.FirstChild.FirstChild.ChildNodes[1].InnerText;
                    names.Add(name);
                }
                
                Bookings.Add(new Booking(area,dTime,names.ToArray()));

                double percentage = (Convert.ToDouble(hrefs.IndexOf(href) + 1) / Convert.ToDouble(hrefs.Count)) * 100.0;
                if  (percentage >= percMin){
                    Console.Write("|");
                    percMin+=10;
                }
            }
            Console.WriteLine("] Done.");
            return true;
        }
    
        public void ClearBookings(){
            Bookings = new List<Booking>();
        }

        public string BuildHTMLSchedule(string dep){
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

                return(htmlStart+htmlMid+htmlEnd);
                }
                
        }
    }
