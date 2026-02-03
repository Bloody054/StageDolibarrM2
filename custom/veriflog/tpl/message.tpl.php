<?php
/* Copyright (C) 2021      Mikael Carlavan        <contact@mika-carl.fr>
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
 *     	\file       htdocs/public/veriflog/tpl/message.tpl.php
 *		\ingroup    cmcic
 */

if (empty($conf->veriflog->enabled))
    exit;

header('Content-type: text/html; charset=utf-8');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta name="robots" content="noindex,nofollow" />
    <title><?php echo $langs->trans('VerifLogRefusedAccessTitle'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang; ?>" />
    <style type="text/css">
        body{
            width : 50%;
            margin: auto;
            text-align : center;
        }

        #content {
            margin-top: 50px;
            font-weight: bold;
            font-size: large;
        }

    </style>
</head>

<body>

<div id="content">
    <h1><?php echo $langs->trans('VerifLogRefusedAccessMessage'); ?></h1><br />
</div>

</body>
</html>
