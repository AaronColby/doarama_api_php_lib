# doarama_api_php_lib
PHP Library function for Doarama API to upload tracks and create visualization

This is a quick example of a uploading activities to Doarama, then authoring a visualization of that activity.  Activities that are on the same day get added to the same visualization.

To view the visualization, you can link to the following link directly or embed in an iframe:

https://api.doarama.com/api/0.2/visualisation?k=<doarama_key>E&name=<track1_nm>&name=<track1_nm>&avatar=http%3A%2F%2Fwww.yourwebsite.com%2F<track1_avatar>.jpg&dzml=http%3A%2F%2www.yourwebsite.com%2Fcircle.dzml

<doarama_key> = doarama_key of the visualization you want to watch

<track1_nm> = name of the first track uploaded.  It appears that names are assigned to tracks in the order that tracks are uploaded/created.  Therefore it is important to have some way to query your database for activities in the visualization in the proper order they were created (timestamp, auto inc primary id, etc)

<track1_avatar> = avatar to assign to track 1.  Ideally this should be 32x32 but it seems other sizes work.  As with track name these are assigned to the tracks in the order they were initially created.  Eventually you'll want to setup your own API to handle avatar assignment. (http://www.doarama.com/api/0.2/docs)

Finally have a look at my example of DZML (based on CZML).  It draws two cylinders and simple path between them.  The language has terrible documentation and terrible support.  Most questions I found were from 2014 or earlier (https://github.com/AnalyticalGraphicsInc/cesium/wiki/CZML-Content - description of the language)
