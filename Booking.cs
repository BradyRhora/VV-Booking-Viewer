using System;
using System.Collections;
using System.Linq;

namespace VV_Viewer{
    class Booking{
        public DateTime StartTime {get;}
        //DateTime EndTime;
        public string Area {get;}
        public string[] Names {get; internal set;}
        public Booking(string area, DateTime start, /*DateTime end,*/ string[] names){
            Area=area;
            StartTime=start;
            //EndTime=end;
            Names=names;
        }  

        public void AddName(string name){
            var nms = Names.ToList();
            nms.Add(name);
            Names=nms.ToArray();
        }
    }
}