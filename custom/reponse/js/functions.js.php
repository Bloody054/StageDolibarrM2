<?php

/* Copyright (C) 2019      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 *	\file       htdocs/reponse/js/functions.php
 *	\ingroup    reponse
 *	\brief      Javascript functions
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory


$db->close();
?>

$(document).ready(function() {
	var url = $("#object-datamatrix").attr('data-image');
	if (url)
	{
		$( "img.photounknown" ).attr("src", url);
		//$( "img.photounknown" ).removeAttr("width");
		$( "img.photounknown" ).css("width", "128px");
	}

	$("#save-form-button").click(function(e){
	   e.preventDefault();
	   $("#save-form").submit();
    });
});
