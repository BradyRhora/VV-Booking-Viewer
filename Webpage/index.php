<html>
    <head>
        <title>Brady's Bytes</title>
        <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

    </head>
    <body onload="startFade()">
        <div id="header">
            <p>Bradys<b>Bytes</b></p>
            <div id="links">
                <table>
                    <tr>
                        <td><a href="games.html">Games</a></td>
                        <td><svg xmlns="http://www.w3.org/2000/svg" width="6" height="6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12"/></svg></td>
                        <td><a href="renders.html">Renders</a></td>
                        <td><svg xmlns="http://www.w3.org/2000/svg" width="6" height="6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12"/></svg></td>
                        <td><a href="contact.html">Contact</a></td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="background">
            <img src="resources/background.jpg" alt="background"/>
            <p id="fadeInText" style="opacity:0%">Haha,<br/>Hey whaddup</p>
        </div>
        <div id="body">
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis elementum tortor non dapibus efficitur. Duis commodo arcu sed leo sollicitudin maximus. Quisque at laoreet lacus, nec pretium diam. Phasellus metus nisi, commodo quis tellus ac, faucibus suscipit lectus. Quisque vel risus interdum, sodales erat id, fringilla metus. Sed vulputate massa id luctus efficitur. Donec nunc erat, pellentesque non nisl vitae, pharetra pretium erat.<br/><br/>

                Etiam accumsan efficitur erat. Vivamus efficitur ante quis nunc blandit tincidunt. Fusce maximus quam a sapien scelerisque auctor. Curabitur est magna, auctor et vehicula non, mattis eget mauris. Vestibulum gravida augue vitae leo placerat pulvinar. Nulla facilisis vestibulum arcu quis gravida. Fusce ac venenatis nulla. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.<br/><br/>
                
                Morbi scelerisque egestas mauris, a tempus ex tempor sit amet. Curabitur semper accumsan pharetra. Etiam at laoreet elit. Aenean iaculis facilisis pellentesque. Nunc suscipit interdum justo ut varius. Curabitur ultrices risus eget tincidunt efficitur. Nam semper auctor elit id dignissim. Vestibulum imperdiet magna eget justo fringilla laoreet. Donec velit lacus, condimentum fermentum cursus nec, facilisis nec tellus.<br/><br/>
                
                Suspendisse potenti. Aliquam eget libero at risus fringilla vehicula vel vitae nisi. Donec ac risus a dolor mollis lobortis vel luctus nisl. Fusce fermentum ipsum quis posuere cursus. Sed ut velit nec turpis consectetur aliquet. Nullam rutrum metus at leo ultricies congue vitae sit amet tortor. Duis nulla sem, lacinia sit amet dictum at, molestie vitae metus. Proin non elit euismod, rhoncus neque sed, molestie arcu. Integer sed nunc bibendum lorem sodales porta et eget lacus. Vestibulum convallis id magna a congue. Pellentesque eu dui a dolor tincidunt cursus. Morbi tellus purus, aliquam ut pellentesque a, aliquam quis nulla.<br/><br/>
                
                Nunc consectetur tristique ante, et mollis mauris molestie at. Nunc id rhoncus est, ut venenatis purus. Ut dignissim mi in cursus congue. Nullam leo lacus, hendrerit quis lacus quis, interdum ornare tellus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Morbi id orci at lorem ornare ultrices sed ac ex. Nunc lobortis aliquet luctus.</p>
        </div>
        <h1><a href="VV-Booking-Viewer-Page/VVViewer.php">Click here to go to VV Booking Viewer</a></h1>
    
        <script>

            var timInt;
            function startFade(){
                setTimeout(function() {timInt = setInterval(fadeIn, 10)},1000);
            }

            function fadeIn(){
                var elem = document.getElementById("fadeInText");
                var op = parseFloat(elem.style.opacity);
                elem.style.opacity=op+.008;
                if (op+.008 >= 1) 
                    clearInterval(timInt);
            }
        </script>
    
    </body>
</html>