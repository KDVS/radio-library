<?php
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

phpBrainz is a php class for querying the musicbrainz web service.
Copyright (c) 2007 Jeff Sherlock

*/

require_once("../phpBrainz.class.php");

//Create new phpBrainz object
$phpBrainz = new phpBrainz();

$args = array(
    "title"=>"Learn",
    "artist"=>"Foo Fighters"
);

$releaseFilter = new phpBrainz_ReleaseFilter($args);
$releaseResults = $phpBrainz->findRelease($releaseFilter);
print_r($releaseResults);
