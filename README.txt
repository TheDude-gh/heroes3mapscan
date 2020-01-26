Heroes III Map Reader


Author: The Dude from novapolis.net
Licence: GNU GENERAL PUBLIC LICENSE Version 3


1. BRIEF DESCRIPTION
  Heroes III Map Reader is web based application for reading Heroes III maps, displaying map's details, saving map to image and saving to sql database.
  It can read ROE, AB, SOD, WOG, ERA and HOTA maps (up to HOTA subrevision 3).
  
  Application is written in PHP, and uses MySQL database.

  You can see example here http://heroes.novapolis.net/mapindex.php


2. REQUIREMENTS
  To run Heroes III Map Reader you will need web server with PHP and MySQL installed.
	The heroesdb_struct.sql contains structure for table to store map data.


3. INSTALLATION
  a) Copy all the files to chosen folder on your webserver.
  b) In fun/config.php file set paths to the required data and access to database. You can leave it as that if you copy data to these folders,
     or you can set your own.
     Also copy and rename fun/access.def.php to fun/access.php and setup database access data in it.


4. USAGE
  Open via browser the folder of the application. There are two scripts.

  a) mapscan.php
     This script will scan map folder and display maps not yet processed and not saved in database.
     By clicking maps individually or by reading all, maps will be saved to database and map pictures created.

  b) mapindex.php
     This script shows list of saved maps, and by clicking on any map, it will show more details.
     Those details are not very user friendly at some points, but the purpose of this application is to provide PHP map reader.
     The interpretation of map data is up to any Heroes III web map provider.
     The application does not display everything in web window, but it reads almost complete map data, like you would see
     in Heroes III map editor,
     like locations of any element, any texts, triggers, and so on. You could display those too, if you would desire so.

5. NOTES
  I have programmed this application in autumn 2016 and then leave it sit for few years. There are still some unfinished parts,
  like building names, maybe artifact names, especially for expansions WOG and HOTA.

6. CREDITS
  Heroes III®

  Big thanks to team working on VCMI project https://vcmi.eu/, which sources I used to study map structure of Heroes III and WOG.
  As for HOTA map structure, I had to decode it more or less myself, but there was not many differences anyway.

  Also thanks to some unnamed another PHP Heroes III map reader I found on web, which also helped with understanding Heroes III maps.
  Currently I don't remember it's name and author, but once I dig that up, it will be credited properly.

  This application also uses 3rd party code, included in GIT:
  jQuery  https://jquery.com/ ... jQuery is under MIT licence, so it is not under GNU GPLv3 like rest of this project. It is only included, so you don't have to obtain it yourself.


