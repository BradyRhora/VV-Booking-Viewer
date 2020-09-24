using System;

namespace VV_Viewer{
    class Booking{
        public DateTime StartTime {get;}
        //DateTime EndTime;
        public string Area {get;}
        public string[] Names {get;}
        public Booking(string area, DateTime start, /*DateTime end,*/ string[] names){
            Area=area;
            StartTime=start;
            //EndTime=end;
            Names=names;
        }
    }
}