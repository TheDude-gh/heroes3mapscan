Heroes III Map Reader
======

Author: The Dude from novapolis.net

Licence: GNU GENERAL PUBLIC LICENSE Version 3


### 1. Brief Description
---------------------------
  Heroes III Map Reader is web based application for reading Heroes III maps, displaying map's details, saving map to image and saving to sql database.

  It can read ROE, AB, SOD, WOG, ERA and HOTA maps (up to HOTA subrevision 5).

  Application is written in PHP, and uses MySQL/MariaDB database.

  You can see example here: [Heroes III Map Reader](https://www.heroesmaps.org/)


### 2. Requirements
---------------------------
  To run Heroes III Map Reader you will need web server with PHP and MySQL installed.

  The **heroesdb_struct.sql** contains structure for table to store map data.


### 3. Installation
---------------------------
  * Copy all the files to chosen folder on your webserver.

  * In **fun/config.php** file set paths to the required data and access to database. You can leave it as that if you copy data to these folders, or you can set your own.
    Also copy and rename **fun/access.def.php** to **fun/access.php** and setup database access data in it.


### 4. Usage
---------------------------
  Open via browser the folder of the application. There are two scripts.

####  a) mapscan.php

  This script will scan map folder and display maps not yet processed and not saved in database.
  By clicking maps individually or by reading all, maps will be saved to database and map pictures created.


####  b) mapindex.php

  This script shows list of saved maps, and by clicking on any map, it will show more details.
  Those details are not very user friendly at some points, but the purpose of this application is to provide PHP map reader.

  The interpretation of map data is up to any Heroes III web map provider.

  The application does not display everything in web window, but it reads almost complete map data you could see in Heroes III map editor, like locations of any element, any texts, triggers, and so on. You could display those too, if you would desire so.

#### Code examples

Reading maps or campaigns is simple as that:

```php
//for both include these files

require_once 'fun/mi.php';
require_once 'fun/config.php';
require_once 'fun/maplistlib.php';
require_once 'fun/h3mapscan.php';
require_once 'fun/h3mapconstants.php';
require_once 'fun/mapsupport.php';

require_once 'fun/h3camscan.php';      //campaign scan only
require_once 'fun/h3camconstants.php'; //campaign scan only
```

```php
/*
read modes

H3M_WEBMODE       required for printinfo
H3M_PRINTINFO     prints map info, requires webmode
H3M_BUILDMAP      builds map image
H3M_SAVEMAPDB     saves map info to DB
H3M_EXPORTMAP     uncompresses and saves pure h3m file
H3M_BASICONLY     reads only basic info about map, for fast read, when active, wont read and build map image
H3M_MAPHTMCACHE   save printinfo htm file, requires printinfo
H3M_SPECIALACCESS displays some objects on map in different color
H3M_TERRAINONLY   reads basic info and terrain only
*/

$mapfile = path/to/map.h3m';
$map = new H3MAPSCAN($mapfile, H3M_WEBMODE | H3M_PRINTINFO | H3M_BUILDMAP);
$map->ReadMap();
```

```php
/*
read modes

H3C_PRINTINFO    prints cam info, requires webmode
H3C_SAVECAMDB    saves cam info to DB
H3C_EXPORTMAPS   export maps
H3C_CAMHTMCACHE  save printinfo htm file, requires printinfo
H3C_CAMSAVEMAPS  save maps to DB
H3C_SCENARIOMAPS save maps directly without DB
*/

$camfile = 'path/to/campaign.h3c';
$map = new H3CAMSCAN($camfile, H3C_PRINTINFO | H3C_EXPORTMAPS | H3C_SCENARIOMAPS);
$map->ReadCam();
$map->ReadMaps();
```

### 5. Credits
---------------------------
  **Heroes III®**

  Big thanks to team working on **[VCMI project](https://vcmi.eu/)**, which sources I used to study map structure of Heroes III and WOG.

  Thanks to HOTA team for providing vital information about HOTA map format updates.

  Also thanks to some unnamed another PHP Heroes III map reader I found on web, which also helped with understanding Heroes III maps.

  Currently I don't remember it's name and author, but once I dig that up, it will be credited properly.

  This application also uses 3rd party code, included in GIT:
  jQuery  https://jquery.com/ ... jQuery is under MIT licence, so it is not under GNU GPLv3 like rest of this project. It is only included, so you don't have to obtain it yourself.


