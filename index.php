<?php
/**
 * Copyright 2015 Thierry BUGEAT
 * 
 * This file is part of myProxy.
 * 
 * myProxy is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * myProxy is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with myProxy.  If not, see <http://www.gnu.org/licenses/>.
 */

ob_start("ob_gzhandler");

/**
 * Variable(s) :
 * - url (url encoded)
 *
 * Usage :
 * - http://54.229.143.103/proxy/<url>
 * 
 * Examples :
 * - http://54.229.143.103/proxy/?url=http%3A%2F%2Flinuxfr.org%2Fnews.atom
 * - http://54.229.143.103/proxy/?url=https%3A%2F%2Fwww.google.com%2Fuds%2FGfeeds%3F%26output%3Djson%26num%3D8%26scoring%3Dh%26q%3Dhttps%3A%2F%2Fwww.reddit.com%2Fr%2FFireFoxOS%2F.rss%26key%3Dnotsupplied%26v%3D1.0%26rnd%3D0.9277437162923303
 *
 * Technical Informations :
 * - 3 servers http01 (haproxy01), http02 (haproxy02), http03
 * */

$url = $_GET['url'];

try {
    $result = file_get_contents($url);
} catch (Eception $e) {
    echo $e->getMessage();
    exit;
}

echo $result;
ob_end_flush();
