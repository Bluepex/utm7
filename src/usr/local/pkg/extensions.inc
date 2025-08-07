<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

function get_ext_files(){
	return array(
	"page_layout" => array(
		"indd"	=>	"Adobe InDesign Document",
		"pct"	=>	"Picture File",
		"pdf"	=>	"Portable Document Format File"
		),
	"audio" => array(
		"aif"	=>	"Audio Interchange File Format",
		"iff"	=>	"Interchange File Format",
		"m3u"	=>	"Media Playlist File",
		"m4a"	=>	"MPEG-4 Audio File",
		"mid"	=>	"MIDI File",
		"mp3"	=>	"MP3 Audio File",
		"mpa"	=>	"MPEG-2 Audio File",
		"ra"	=>	"Real Audio File",
		"wav"	=>	"WAVE Audio File",
		"wma"	=>	"Windows Media Audio File"
		),
	"video" => array(
		"3g2"	=>	"3GPP2 Multimedia File",
		"3gp"	=>	"3GPP Multimedia File",
		"asf"	=>	"Advanced Systems Format File",
		"asx"	=>	"Microsoft ASF Redirector File",
		"avi"	=>	"Audio Video Interleave File",
		"flv"	=>	"Flash Video File",
		"m4v"	=>	"iTunes Video File",
		"mov"	=>	"Apple QuickTime Movie",
		"mp4"	=>	"MPEG-4 Video File",
		"mpg"	=>	"MPEG Video File",
		"rm"	=>	"Real Media File",
		"srt"	=>	"SubRip Subtitle File",
		"swf"	=>	"Shockwave Flash Movie",
		"vob"	=>	"DVD Video Object File",
		"wmv"	=>	"Windows Media Video File"
		),	
	"data" => array(
		"csv"	=>	"Comma Separated Values File",
		"dat"	=>	"Data File",
		"gbr"	=>	"Gerber File",
		"ged"	=>	"GEDCOM Genealogy Data File",
		"ibooks"	=>	"Multi-Touch iBook",
		"key"	=>	"Keynote Presentation",
		"keychain"	=>	"Mac OS X Keychain File",
		"pps"	=>	"PowerPoint Slide Show",
		"ppt"	=>	"PowerPoint Presentation",
		"pptx"	=>	"PowerPoint Open XML Presentation",
		"sdf"	=>	"Standard Data File",
		"tar"	=>	"Consolidated Unix File Archive",
		"tax2012"	=>	"TurboTax 2012 Tax Return",
		"vcf"	=>	"vCard File",
		"xml"	=>	"XML File"
		),	
	"disk" => array(
		"bin"	=>	"Binary Disc Image",
		"cue"	=>	"Cue Sheet File",
		"dmg"	=>	"Mac OS X Disk Image",
		"iso"	=>	"Disc Image File",
		"mdf"	=>	"Media Disc Image File",
		"toast"	=>	"Toast Disc Image",
		"vcd"	=>	"Virtual CD"
		),
	"game" => array(
		"dem"	=>	"Video Game Demo File",
		"gam"	=>	"Saved Game File",
		"nes"	=>	"Nintendo NES ROM File",
		"rom"	=>	"N64 Game ROM File",
		"sav"	=>	"Saved Game"
		),
	"cad" => array(
		"dwg"	=>	"AutoCAD Drawing Database File",
		"dxf"	=>	"Drawing Exchange Format File"
		),
	"backup" => array(
		"bak"	=>	"Backup File",
		"tmp"	=>	"Temporary File"
		),
	"images3d" => array(
		"3dm"	=>	"Rhino 3D Model",
		"3ds"	=>	"3D Studio Scene",
		"max"	=>	"3ds Max Scene File",
		"obj"	=>	"Wavefront 3D Object File"
		),
	"plugin" => array(
		"crx"	=>	"Chrome Extension",
		"plugin"	=>	"Mac OS X Plug-in"
		),
	"developer" => array(
		"c"	=>	"C/C++ Source Code File",
		"class"	=>	"Java Class File",
		"cpp"	=>	"C++ Source Code File",
		"cs"	=>	"Visual C# Source Code File",
		"dtd"	=>	"Document Type Definition File",
		"fla"	=>	"Adobe Flash Animation",
		"h"	=>	"C/C++/Objective-C Header File",
		"java"	=>	"Java Source Code File",
		"lua"	=>	"Lua Source File",
		"m"	=>	"Objective-C Implementation File",
		"pl"	=>	"Perl Script",
		"py"	=>	"Python Script",
		"sh"	=>	"Bash Shell Script",
		"sln"	=>	"Visual Studio Solution File",
		"vcxproj"	=>	"Visual C++ Project",
		"xcodeproj"	=>	"Xcode Project"
		),
	"settings" => array(
		"cfg"	=>	"Configuration File",
		"ini"	=>	"Windows Initialization File",
		"prf"	=>	"Outlook Profile File"
		),
	"encoded" => array(
		"hqx"	=>	"BinHex 4.0 Encoded File",
		"mim"	=>	"Multi-Purpose Internet Mail Message File",
		"uue"	=>	"Uuencoded File"
		),
	"text" => array(
		"doc"	=>	"Microsoft Word Document",
		"docx"	=>	"Microsoft Word Open XML Document",
		"log"	=>	"Log File",
		"msg"	=>	"Outlook Mail Message",
		"odt"	=>	"OpenDocument Text Document",
		"pages"	=>	"Pages Document",
		"rtf"	=>	"Rich Text Format File",
		"tex"	=>	"LaTeX Source Document",
		"txt"	=>	"Plain Text File",
		"wpd"	=>	"WordPerfect Document",
		"wps"	=>	"Microsoft Works Word Processor Document"
		),
	"raster" => array(
		"bmp"	=>	"Bitmap Image File",
		"dds"	=>	"DirectDraw Surface",
		"gif"	=>	"Graphical Interchange Format File",
		"jpg"	=>	"JPEG Image",
		"png"	=>	"Portable Network Graphic",
		"psd"	=>	"Adobe Photoshop Document",
		"pspimage"	=>	"PaintShop Pro Image",
		"tga"	=>	"Targa Graphic",
		"thm"	=>	"Thumbnail Image File",
		"tif"	=>	"Tagged Image File",
		"tiff"	=>	"Tagged Image File Format",
		"yuv"	=>	"YUV Encoded Image File"
		),
	"web" => array(
		"asp"	=>	"Active Server Page",
		"aspx"	=>	"Active Server Page Extended File",
		"cer"	=>	"Internet Security Certificate",
		"cfm"	=>	"ColdFusion Markup File",
		"csr"	=>	"Certificate Signing Request File",
		"css"	=>	"Cascading Style Sheet",
		"htm"	=>	"Hypertext Markup Language File",
		"html"	=>	"Hypertext Markup Language File",
		"js"	=>	"JavaScript File",
		"jsp"	=>	"Java Server Page",
		"php"	=>	"PHP Source Code File",
		"rss"	=>	"Rich Site Summary",
		"xhtml"	=>	"Extensible Hypertext Markup Language File"
		),
	"vector" => array(
		"ai"	=>	"Adobe Illustrator File",
		"eps"	=>	"Encapsulated PostScript File",
		"ps"	=>	"PostScript File",
		"svg"	=>	"Scalable Vector Graphics File"
		),
	"executable" => array(
		"apk"	=>	"Android Package File",
		"app"	=>	"Mac OS X Application",
		"bat"	=>	"DOS Batch File",
		"cgi"	=>	"Common Gateway Interface Script",
		"com"	=>	"DOS Command File",
		"exe"	=>	"Windows Executable File",
		"gadget"	=>	"Windows Gadget",
		"jar"	=>	"Java Archive File",
		"pif"	=>	"Program Information File",
		"vb"	=>	"VBScript File",
		"wsf"	=>	"Windows Script File"
		),
	"database" => array(
		"accdb"	=>	"Access 2007 Database File",
		"db"	=>	"Database File",
		"dbf"	=>	"Database File",
		"mdb"	=>	"Microsoft Access Database",
		"pdb"	=>	"Program Database",
		"sql"	=>	"Structured Query Language Data File"
		),
	"system" => array(
		"cab"	=>	"Windows Cabinet File",
		"cpl"	=>	"Windows Control Panel Item",
		"cur"	=>	"Windows Cursor",
		"deskthemepack"	=>	"Windows 8 Desktop Theme Pack File",
		"dll"	=>	"Dynamic Link Library",
		"dmp"	=>	"Windows Memory Dump",
		"drv"	=>	"Device Driver",
		"icns"	=>	"Mac OS X Icon Resource File",
		"ico"	=>	"Icon File",
		"lnk"	=>	"Windows File Shortcut",
		"sys"	=>	"Windows System File"
		),
	"gis" => array(
		"gpx"	=>	"GPS Exchange File",
		"kml"	=>	"Keyhole Markup Language File",
		"kmz"	=>	"Google Earth Placemark File"
		),
	"font" => array(
		"fnt"	=>	"Windows Font File",
		"fon"	=>	"Generic Font File",
		"otf"	=>	"OpenType Font",
		"ttf"	=>	"TrueType Font"
		),
	"spreadsheet" => array(
		"xlr"	=>	"Works Spreadsheet",
		"xls"	=>	"Excel Spreadsheet",
		"xlsx"	=>	"Microsoft Excel Open XML Spreadsheet"
		),
	"compressed" => array(
		"7z"	=>	"7-Zip Compressed File",
		"cbr"	=>	"Comic Book RAR Archive",
		"deb"	=>	"Debian Software Package",
		"gz"	=>	"Gnu Zipped Archive",
		"pkg"	=>	"Mac OS X Installer Package",
		"rar"	=>	"WinRAR Compressed Archive",
		"rpm"	=>	"Red Hat Package Manager File",
		"sitx"	=>	"StuffIt X Archive",
		"tar.gz"	=>	"Compressed Tarball File",
		"zip"	=>	"Zipped File",
		"zipx"	=>	"Extended Zip File"
		)
	);
}
?>
