<?php
/*
This library is to process external files of different types
Adopted mostly from DonRamon
http://habrahabr.ru/blogs/php/70119/
http://habrahabr.ru/blogs/php/69417/
*/

// function getTextFromZippedXML
// allows to work with .docx and .odt files
// thanks to DonRamon http://habrahabr.ru/blogs/php/69417/

function getTextFromZippedXML($archiveFile, $contentFile) {
	// create zip archive in the memory
	$zip = new ZipArchive;
	// open zip file
	if ($zip->open($archiveFile)) {
		// check the file in the archive
		if (($index = $zip->locateName($contentFile)) !== false) {
			// if found read in text variable
			$content = $zip->getFromIndex($index);
			// close the archive, we don't need it anymore
			$zip->close();
			// sw
			$content=str_replace("<w:p ","\n<w:p ",$content);
			// TODO add all entities and includes
			// skip all errors and warnings
			$xml = DOMDocument::loadXML($content, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
			// return data without wml tags
			return strip_tags($xml->saveXML());
		} else {echo "Not found!";}
		$zip->close();
	}
     // if something wron return ERROR text
     return "ERROR in text Tokenization";
}

//

// functions rtf_isPlainText and rtf2text
// support RTF files
// 
// thanks to DonRamon http://habrahabr.ru/blogs/php/70119/

function rtf_isPlainText($s) {
    $failAt = array("*", "fonttbl", "colortbl", "datastore", "themedata");
    for ($i = 0; $i < count($failAt); $i++)
        if (!empty($s[$failAt[$i]])) return false;
    return true;
}

function rtf2text($filename) {
    $text = file_get_contents($filename);
    if (!strlen($text))
        return "";


    // start with empty stack of modifiers
    $document = "";
    $stack = array();
    $j = -1;

    // read chars from buffer...
    for ($i = 0, $len = strlen($text); $i < $len; $i++) {
        $c = $text[$i];

        // select what to do with the current char
        switch ($c) {
            // the most important key \
            case "\\":
                // read the next char
                $nc = $text[$i + 1];

		// put into the out stream
                if ($nc == '\\' && rtf_isPlainText($stack[$j])) $document .= '\\';
                elseif ($nc == '~' && rtf_isPlainText($stack[$j])) $document .= ' ';
                elseif ($nc == '_' && rtf_isPlainText($stack[$j])) $document .= '-';
                // * goes to stack
                elseif ($nc == '*') $stack[$j]["*"] = true;
                elseif ($nc == "'") {
                    $hex = substr($text, $i + 2, 2);
                    if (rtf_isPlainText($stack[$j]))
                        $document .= html_entity_decode("&#".hexdec($hex).";");
                    // move the index
                    $i += 2;
                // read the key symbol
                } elseif ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
                    $word = "";
                    $param = null;

                    // read after \
                    for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) {
                        $nc = $text[$k];

                        if ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
                            if (empty($param))
                                $word .= $nc;
                            else
                                break;

                        } elseif ($nc >= '0' && $nc <= '9')
                            $param .= $nc;

                        elseif ($nc == '-') {
                            if (empty($param))
                                $param .= $nc;
                            else
                                break;
                        // end
                        } else
                            break;
                    }
                    // move the index
                    $i += $m - 1;

                    // read the word
                    $toText = "";
                    switch (strtolower($word)) {

                        case "u":
                            $toText .= html_entity_decode("&#x".dechex($param).";");
                            $ucDelta = @$stack[$j]["uc"];
                            if ($ucDelta > 0)
                                $i += $ucDelta;
                        break;

                        case "par": case "page": case "column": case "line": case "lbr":
                            $toText .= "\n"; 
                        break;
                        case "emspace": case "enspace": case "qmspace":
                            $toText .= " "; 
                        break;
                        case "tab": $toText .= "\t"; break;

                        case "chdate": $toText .= date("m.d.Y"); break;
                        case "chdpl": $toText .= date("l, j F Y"); break;
                        case "chdpa": $toText .= date("D, j M Y"); break;
                        case "chtime": $toText .= date("H:i:s"); break;

                        case "emdash": $toText .= html_entity_decode("&mdash;"); break;
                        case "endash": $toText .= html_entity_decode("&ndash;"); break;
                        case "bullet": $toText .= html_entity_decode("&#149;"); break;
                        case "lquote": $toText .= html_entity_decode("&lsquo;"); break;
                        case "rquote": $toText .= html_entity_decode("&rsquo;"); break;
                        case "ldblquote": $toText .= html_entity_decode("&laquo;"); break;
                        case "rdblquote": $toText .= html_entity_decode("&raquo;"); break;

                        default:
                            $stack[$j][strtolower($word)] = empty($param) ? true : $param;
                        break;
                    }

                    if (rtf_isPlainText($stack[$j]))
                        $document .= $toText;
                }

                $i++;
            break;

            case "{":
                array_push($stack, $stack[$j++]);
            break;
            // } removes current stack. Group is over.
            case "}":
                array_pop($stack);
                $j--;
            break;
            // 
            case '\0': case '\r': case '\f': case '\n': break;
            // 
            default:
                if (rtf_isPlainText($stack[$j]))
                    $document .= $c;
            break;
        }
    }
    // 				
    return $document;
}// end rtf2text

// Reading text from PDF
// Версия 0.3
// Author: Алексей Рембиш a.k.a Ramon
// E-mail: alex@rembish.ru
// Copyright 2009
// Partial translation by Sergey

function decodeAsciiHex($input) {
    $output = "";

    $isOdd = true;
    $isComment = false;

    for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
        $c = $input[$i];

        if($isComment) {
            if ($c == '\r' || $c == '\n')
                $isComment = false;
            continue;
        }

        switch($c) {
            case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
            case '%': 
                $isComment = true;
            break;

            default:
                $code = hexdec($c);
                if($code === 0 && $c != '0')
                    return "";

                if($isOdd)
                    $codeHigh = $code;
                else
                    $output .= chr($codeHigh * 16 + $code);

                $isOdd = !$isOdd;
            break;
        }
    }

    if($input[$i] != '>')
        return "";

    if($isOdd)
        $output .= chr($codeHigh * 16);

    return $output;
}
function decodeAscii85($input) {
    $output = "";

    $isComment = false;
    $ords = array();
    
    for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
        $c = $input[$i];

        if($isComment) {
            if ($c == '\r' || $c == '\n')
                $isComment = false;
            continue;
        }

        if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
            continue;
        if ($c == '%') {
            $isComment = true;
            continue;
        }
        if ($c == 'z' && $state === 0) {
            $output .= str_repeat(chr(0), 4);
            continue;
        }
        if ($c < '!' || $c > 'u')
            return "";

        $code = ord($input[$i]) & 0xff;
        $ords[$state++] = $code - ord('!');

        if ($state == 5) {
            $state = 0;
            for ($sum = 0, $j = 0; $j < 5; $j++)
                $sum = $sum * 85 + $ords[$j];
            for ($j = 3; $j >= 0; $j--)
                $output .= chr($sum >> ($j * 8));
        }
    }
    if ($state === 1)
        return "";
    elseif ($state > 1) {
        for ($i = 0, $sum = 0; $i < $state; $i++)
            $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
        for ($i = 0; $i < $state - 1; $i++)
            $output .= chr($sum >> ((3 - $i) * 8));
    }

    return $output;
}
function decodeFlate($input) {
    // The most common compression method for data streams in PDF.
    // Very easy to deal with using libraries.
    return @gzuncompress($input);
}

function getObjectOptions($object) {
    // We need to get current object attrbutes. These attributes are 
    // located between << and >>. Each option starts with /.
    $options = array();
    if (preg_match("#<<(.*)>>#ismU", $object, $options)) {
        // Separate options from each other using /. First empty one should be removed from the array.
        $options = explode("/", $options[1]);
        @array_shift($options);

        // Create handy array for current object attributes
        // Attributs that look like "/Option N" will be written to hash
        // as "Option" => N, and properties like "/Param", will be written as
        // "Param" => true.
        $o = array();
        for ($j = 0; $j < @count($options); $j++) {
            $options[$j] = preg_replace("#\s+#", " ", trim($options[$j]));
            if (strpos($options[$j], " ") !== false) {
                $parts = explode(" ", $options[$j]);
                $o[$parts[0]] = $parts[1];
            } else
                $o[$options[$j]] = true;
        }
        $options = $o;
        unset($o);
    }

    // Return an array of parameters we found
    return $options;
}
function getDecodedStream($stream, $options) {
    // Now we have a stream that is possibly coded with some compression method(s)
    // Lets try to decode it.
    $data = "";
    // If current stream has Filter attribute, then is is definately compressed or en coded
    // Otherwise just return the content
    if (empty($options["Filter"]))
        $data = $stream;
    else {
        // If we know the size of data stream from options then we need to cut the data
        // using this size, or we may not be able to decode it or maybe something else will go wring
        $length = !empty($options["Length"]) ? $options["Length"] : strlen($stream);
        $_stream = substr($stream, 0, $length);

        // Looping through options looking for indicatiors of data compression in the current stream.
        // PDF supprts many different stuff, but text can be coded either by ASCII Hex, or ASCII 85-base or GZ/Deflate
        // We need to look for these keys and apply respecrtive functions for decoding.
        // There is another option: Crypt, but we are not going to work with encrypted PDF's.
        foreach ($options as $key => $value) {
            if ($key == "ASCIIHexDecode")
                $_stream = decodeAsciiHex($_stream);
            if ($key == "ASCII85Decode")
                $_stream = decodeAscii85($_stream);
            if ($key == "FlateDecode")
                $_stream = decodeFlate($_stream);
        }
        $data = $_stream;
    }
    // Return the result
    return $data;
}
function getDirtyTexts(&$texts, $textContainers) {
    // So we have an array of text contatiners that were taken from both  BT and ET.
    // Our new task is to find a text in them that would be displayed by viewers
    // on the screen. There are many options to do that, Lets check the pair: [...] TJ and Td (...) Tj
    for ($j = 0; $j < count($textContainers); $j++) {
        // Add the pieces of row data the we found to the general array of text objects.
        if (preg_match_all("#\[(.*)\]\s*TJ#ismU", $textContainers[$j], $parts))
            $texts = array_merge($texts, @$parts[1]);
        elseif(preg_match_all("#Td\s*(\(.*\))\s*Tj#ismU", $textContainers[$j], $parts))
            $texts = array_merge($texts, @$parts[1]);
    }
}
function getCharTransformations(&$transformations, $stream) {
    // Oh Mama Mia! As far as I know nobody did it before. At least not in the open source. 
    // We are going to have some fun now - search in symbol transformation streams.
    // Under transforation I mean conversion of ony symbol to hex form or even to some kind of sequence.

    // We need 	all the attributes that we can find in the current stream.
    // Data between  beginbfchar and endbfchar transform one hex-code intn another (or sequence of codes) 
    // separately. Between beginbfrange and endbfrange the transformation of data sequences is taking place
    // and it reduces the number of definitions.
    preg_match_all("#([0-9]+)\s+beginbfchar(.*)endbfchar#ismU", $stream, $chars, PREG_SET_ORDER);
    preg_match_all("#([0-9]+)\s+beginbfrange(.*)endbfrange#ismU", $stream, $ranges, PREG_SET_ORDER);

    // First of all process separate symbols. Transformaiton string looks as follows:
    // - <0123> <abcd> -> 0123 should be transformed to abcd;
    // - <0123> <abcd6789> -> 0123 should be transformed to many symbols (abcd and 6789 in this case)
    for ($j = 0; $j < count($chars); $j++) {
        // There is a number of strings before data list that we are going ot read. We gonna use it later on.
        $count = $chars[$j][1];
        $current = explode("\n", trim($chars[$j][2]));
        // Read data from each string.
        for ($k = 0; $k < $count && $k < count($current); $k++) {
            // Wrute the transformation we just found. Don't forget about writing leading zeros if there are less then 4 digits..
            if (preg_match("#<([0-9a-f]{2,4})>\s+<([0-9a-f]{4,512})>#is", trim($current[$k]), $map))
                $transformations[str_pad($map[1], 4, "0")] = $map[2];
        }
    }
    // Now we can deal with sequences. Manuals are saying that they can be one of two possible types
    // - <0000> <0020> <0a00> -> in this case  <0000> will be substituted with <0a00>, <0001> with <0a01> and so on
    //   till  <0020>, that will be substituted with <0a20>.
    // OR
    // - <0000> <0002> [<abcd> <01234567> <8900>] -> here it works in a bit different way. We need to look how
    //   many elemants are located between  <0000> and <0002> (its actually three including 0001). After it we assign to each element 
    //   a corresponding value from [ ]: 0000 -> abcd, 0001 -> 0123 4567, а 0002 -> 8900.
    for ($j = 0; $j < count($ranges); $j++) {
        // We need to cross check the number of elements for transofrmation.
        $count = $ranges[$j][1];
        $current = explode("\n", trim($ranges[$j][2]));
        // Working with each string
        for ($k = 0; $k < $count && $k < count($current); $k++) {
            // This is first type sequence.
            if (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+<([0-9a-f]{4})>#is", trim($current[$k]), $map)) {
                // Convert data into decimal system: looping will be easier.
                $from = hexdec($map[1]);
                $to = hexdec($map[2]);
                $_from = hexdec($map[3]);

                // We put all the elements from the sequence into transformations array.
                // According to manuals we need also to ass leading zeros if hex-code size is less than 4 symbols.
                for ($m = $from, $n = 0; $m <= $to; $m++, $n++)
                    $transformations[sprintf("%04X", $m)] = sprintf("%04X", $_from + $n);
            // Second option.
            } elseif (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+\[(.*)\]#ismU", trim($current[$k]), $map)) {
                // This is also beginnigna nd end of the sequence. Split data in [ ] by symbols located near to spaces.
                $from = hexdec($map[1]);
                $to = hexdec($map[2]);
                $parts = preg_split("#\s+#", trim($map[3]));
                
                // Loop through data and assign the new values accordingly.
                for ($m = $from, $n = 0; $m <= $to && $n < count($parts); $m++, $n++)
                    $transformations[sprintf("%04X", $m)] = sprintf("%04X", hexdec($parts[$n]));
            }
        }
    }
}
function getTextUsingTransformations($texts, $transformations) {
    // Second phase - getting text out of raw data.
    // In PDF "dirty" text strings may look as follows:
    // - (I love)10(PHP) - in this case text data a re located in  (),
    //   and  10 is number of spaces.
    // - <01234567> - in this case we deal with 2 symbols represented in HEX:
    //   : 0123 and 4567. Substitutions for both should be checked inthe substitution table.
    // - (Hello, \123world!) - here \123 is symbol in octal system and we need to handle it properly.

    // Lets go. We are accumulating text data processign "raw" pieces of text
    $document = "";
    for ($i = 0; $i < count($texts); $i++) {
        // 2 cases are possible: text can be either in <> (hex) or in () (plain).
        $isHex = false;
        $isPlain = false;

        $hex = "";
        $plain = "";
        // scan current piece of text.
        for ($j = 0; $j < strlen($texts[$i]); $j++) {
            // get current char
            $c = $texts[$i][$j];
            // ...and decide what to do with it.
            switch($c) {
                // We have hex data in front of us
                case "<":
                    $hex = "";
                    $isHex = true;
                break;
                // Hex data are over. Lets parse them.
                case ">":
                    // split the string into chunks of 4 chars...
                    $hexs = str_split($hex, 4);
                    // ...and cheking what we can do with each chunk
                    for ($k = 0; $k < count($hexs); $k++) {
                        // if there are less then 4 symbols then the manual says that we need to add zeros after them
                        $chex = str_pad($hexs[$k], 4, "0");
                        // Checking if current hex-code is already in transformations. 
                        // If this is the case change this piece to the required.
                        if (isset($transformations[$chex]))
                            $chex = $transformations[$chex];
                        // Write a new Unicode symbol into the output .
                        $document .= html_entity_decode("&#x".$chex.";");
                    }
                    // Hex-sata are over. Need to say it.
                    $isHex = false;
                break;
                // There is a piece of "plain" text
                case "(":
                    $plain = "";
                    $isPlain = true;
                break;
                // Well... this piece will be over sometime.
                case ")":
                    // Get the text we just got into the output stream.
                    $document .= $plain;
                    $isPlain = false;
                break;
                // Specail symbol. Lets see what is located after it.
                case "\\":
                    $c2 = $texts[$i][$j + 1];
                    // If it is  \ ot either one of ( or ), then print them as it is.
                    if (in_array($c2, array("\\", "(", ")"))) $plain .= $c2;
                    // If it is empty space of EOL then process it.
                    elseif ($c2 == "n") $plain .= '\n';
                    elseif ($c2 == "r") $plain .= '\r';
                    elseif ($c2 == "t") $plain .= '\t';
                    elseif ($c2 == "b") $plain .= '\b';
                    elseif ($c2 == "f") $plain .= '\f';
                    // It might happen that a digit follows after \ . It may be up to 3 of them. 
                    // They represent sybmol code in octal system. Lets parse them.
                    elseif ($c2 >= '0' && $c2 <= '9') {
                        // We need 3 digits. No more than 3. Digits only.
                        $oct = preg_replace("#[^0-9]#", "", substr($texts[$i], $j + 1, 3));
                        // Getting the number of characters we already have taken. We need it to shift the position of current char properly.
                        $j += strlen($oct) - 1;
                        // Put the respective char into "plain" text.
                        $plain .= html_entity_decode("&#".octdec($oct).";");
                    }
                    // We increased the position of current symbol at least by one. Need to inform parser about that.
                    $j++;
                break;

                // If we have something else then write current symbol into temporaty hex string (if we had < before),
                default:
                    if ($isHex)
                        $hex .= $c;
                    // or into "plain" string if ( was opeon.
                    if ($isPlain)
                        $plain .= $c;
                break;
            }
        }
        // Define text blocks by EOL
        $document .= "\n";
    }

    // Return text.
    return $document;
}

function pdf2text($filename) {
    // Read from the pdf file into string keeping in mind that file may contain binary streams
    $infile = @file_get_contents($filename, FILE_BINARY);
    if (empty($infile))
        return "";

    // First iteration. We need to get all the text data from file.
    // We'll get only "raw" data after the firs iteration. These data will include positioning, 
    // hex entries, etc.
    $transformations = array();
    $texts = array();

    // Get list of all files from pdf file.
    preg_match_all("#obj(.*)endobj#ismU", $infile, $objects);
    $objects = @$objects[1];

    // Let start the crawling. Apart fromthe text we can meet some other stuff including fonts.
    for ($i = 0; $i < count($objects); $i++) {
        $currentObject = $objects[$i];

        // Check if there is data stream in the current object. 
        // Almost all the time it will be compressed with gzip.
        if (preg_match("#stream(.*)endstream#ismU", $currentObject, $stream)) {
            $stream = ltrim($stream[1]);

            // Read the attributes of this object. We are looking only 
            // for text, so we have to do minimal cuts to improve the speed
            $options = getObjectOptions($currentObject);
            if (!(empty($options["Length1"]) && empty($options["Type"]) && empty($options["Subtype"])))
                continue;

            // So, we "may" have text in from of us. Lets decode it from binary file to get the plain text.
            $data = getDecodedStream($stream, $options); 
            if (strlen($data)) {
                // We need to find text container in the current stream. 
                // If we will be able to get it the raw text we found will be added to the previous findings. 
                if (preg_match_all("#BT(.*)ET#ismU", $data, $textContainers)) {
                    $textContainers = @$textContainers[1];
                    getDirtyTexts($texts, $textContainers);
                // Otherwise we'll try to use symbol transformations that we gonna use on the 2nd step.
                } else
                    getCharTransformations($transformations, $data);
            }
        }
    }

    // After the preliminary parsing of  pdf-document we need to parse 
    // the text blocks we got in the context of simbolic transformations. Return the result after we done.
    return getTextUsingTransformations($texts, $transformations);
}

// Reading WCBFF
// Version 0.2
// Author: Алексей Рембиш a.k.a Ramon
// E-mail: alex@rembish.ru
// Copyright 2009

// so my little firends, below you can see class that works with WCBFF (Windows Compound Binary File Format). 
// Why do we need it? This format serves as a basement for such "delicious" formats as .doc, .xls и .ppt. 
// Lets see how it looks like
class cfb {
    // We gonna read the content of the file we need to decode into this variable.
    protected $data = "";

    // Sizes of FAT sector (1 << 9 = 512), Mini FAT sector (1 << 6 = 64) and maximum size 
    // of the stream that could be written into a miniFAT.
    protected $sectorShift = 9;
    protected $miniSectorShift = 6;
    protected $miniSectorCutoff = 4096;

    // FAT-sector sequence array and Array of "files" belonging to this file structure
    protected $fatChains = array();
    protected $fatEntries = array();

    // Array of sequences of  Mini FAT-sectors and the whole Mini FAT of our file
    protected $miniFATChains = array();
    protected $miniFAT = "";

    // Version (3 or 4), and way to write numbers (little-endian)
    private $version = 3;
    private $isLittleEndian = true;

    // The number of "files" and the position fo the first "file" in FAT
    private $cDir = 0;
    private $fDir = 0;

    // The number of FAT sectors in the file
    private $cFAT = 0;

    // The number of miniFAT-sectors and position of sequences of miniFAT-сsectors in the file
    private $cMiniFAT = 0;
    private $fMiniFAT = 0;

    // DIFAT: number of such sectors and offset to sector 110 (first 109 sectors are located in the header)
    private $DIFAT = array();
    private $cDIFAT = 0;
    private $fDIFAT = 0;

    // Constants: end of sequence and empty sector (4 bytes each)
    const ENDOFCHAIN = 0xFFFFFFFE;
    const FREESECT   = 0xFFFFFFFF;

    // Read the file into internal variable
    public function read($filename) {
        $this->data = file_get_contents($filename);
    }

    public function parse() {
        // First of all we need to check weither we really have CFB in front of us.?
        // To do it we read the first 8 bytes and compare them with 2 patterns: common and the old one
        $abSig = strtoupper(bin2hex(substr($this->data, 0, 8)));
        if ($abSig != "D0CF11E0A1B11AE1" && $abSig != "0E11FC0DD0CF11E0") { return false; }

        // Read the file header;
        $this->readHeader();
        // get the remaining DIFAT sectors if any;
        $this->readDIFAT();
        // read the sequence of FAT sectors
        $this->readFATChains();
        // read the sequence of MiniFAT-sectors
        $this->readMiniFATChains();
        // read the structure of "directories" within the file
        $this->readDirectoryStructure();

        // Finally we need to check the root entry in the file structure. 
        // This stream is required ot be in a file at least because it has a link 
        // to file's miniFAT that we gonna read into $this->miniFAT
        
        $reStreamID = $this->getStreamIdByName("Root Entry");
        if ($reStreamID === false) { return false; }
        $this->miniFAT = $this->getStreamById($reStreamID, true);

        // Remove the unnecessary link to DIFAT-sectors, we have "stolen" complete FAT sequences instead of them.
        unset($this->DIFAT);

        // After all this we should be able to work with any of the "upper" formats from Microsoft such as doc, xls или ppt.
    }

    // Function that looks for stream number in the directory structure by its name. 
    // It returns false if nothing was found.
    public function getStreamIdByName($name) {
        for($i = 0; $i < count($this->fatEntries); $i++) {
            if ($this->fatEntries[$i]["name"] == $name)
                return $i;
        }
        return false;
    }
    // Function gets the stream number ($id) and a second parameter (second perameter is required for the root entry only).
    // It returns the binary content fo this stream.
    public function getStreamById($id, $isRoot = false) {
        $entry = $this->fatEntries[$id];
        // Get the size and offset position to the content of "current" file.
        $from = $entry["start"];
        $size = $entry["size"];

        // Now 2 options are possible: is size is less than  4096 byte, then we need ot read data
        // from MiniFAT. If more than 4096 read from the common FAT. RootEntry is an exclusion:
        // we need ot read contents from FAT as miniFAT is located there.

        $stream = "";
        // So, here is the 1st option: small size and not root.
        if ($size < $this->miniSectorCutoff && !$isRoot) {
            // Get the miniFAT sector size - 64 bytes
            $ssize = 1 << $this->miniSectorShift;

            do {
                // Get the offset in miniFAT
                $start = $from << $this->miniSectorShift;
                // Read miniFAT-sector
                $stream .= substr($this->miniFAT, $start, $ssize);
                // Get the next piece of miniFAT in the array of chains
                $from = $this->miniFATChains[$from];
                // While not end of chain (sequence).
            } while ($from != self::ENDOFCHAIN);
        } else {
            // Second option - large piece - read it from FAT.
            // Get the sector size  - 512 (or 4096 for new versions)
            $ssize = 1 << $this->sectorShift;
            
            do {
                // Getting the offset in the file (taking into account that there is a header of 512 bytes in the begining)
                $start = ($from + 1) << $this->sectorShift;
                // Read a sector
                $stream .= substr($this->data, $start, $ssize);
                // Get the next sector inthe array of FAT chains
                $from = $this->fatChains[$from];
                // While not end of chain (sequence).
            } while ($from != self::ENDOFCHAIN);
        }
        // Return the stream content accrding to its size.
        return substr($stream, 0, $size);
    }

    // This function reads data from file header
    private function readHeader() {
        // We need to get the information about the data format in the file
        $uByteOrder = strtoupper(bin2hex(substr($this->data, 0x1C, 2)));
        // We need to check if it is  little-endian record
        $this->isLittleEndian = $uByteOrder == "FEFF";

        // Version 3 or 4 (never actually met 4th, but its description appears in the manual)
        $this->version = $this->getShort(0x1A);

        // Offsets for FAT and miniFAT
        $this->sectorShift = $this->getShort(0x1E);
        $this->miniSectorShift = $this->getShort(0x20);
        $this->miniSectorCutoff = $this->getLong(0x38);

        // Number of entries in the directory and offset to the first description in the file
        if ($this->version == 4)
            $this->cDir = $this->getLong(0x28);
        $this->fDir = $this->getLong(0x30);

        // Number of FAT sectors in the file
        $this->cFAT = $this->getLong(0x2C);

        // Number and position of hte 1st miniFAT-sector of sequences.
        $this->cMiniFAT = $this->getLong(0x40);
        $this->fMiniFAT = $this->getLong(0x3C);

        // Where are the FAT sector chains and how many of them are there.
        $this->cDIFAT = $this->getLong(0x48);
        $this->fDIFAT = $this->getLong(0x44);
    }

    // So.... DIFAT. DIFAT shows in which sectors we can find descriptions of FAT sector chains
    // Without these chains we won't be able to get stream contents in fragmented files
    private function readDIFAT() {
        $this->DIFAT = array();
        // First 109 links to sequences are being stored in the header of our file
        for ($i = 0; $i < 109; $i++)
            $this->DIFAT[$i] = $this->getLong(0x4C + $i * 4);

        // we also check if there are other links to chains. in small (upto 8.5MB) there is no such
        // links but in larger files we have to read them.
        if ($this->fDIFAT != self::ENDOFCHAIN) {
            // Sector size and start position to read links.
            $size = 1 << $this->sectorShift;
            $from = $this->fDIFAT;
            $j = 0;

            do {
                // Get the position in the file considering header
                $start = ($from + 1) << $this->sectorShift;
                // Read the links to sequences' sectors
                for ($i = 0; $i < ($size - 4); $i += 4)
                    $this->DIFAT[] = $this->getLong($start + $i);
                // Getting the next  DIFAT-sector. Link to this sector is written
                // as the last "word" in the current  DIFAT-sector
                $from = $this->getLong($start + $i);
                // Ef sector exists we need to move there
            } while ($from != self::ENDOFCHAIN && ++$j < $this->cDIFAT);
        }

        // Remove the unnecessary links.
        while($this->DIFAT[count($this->DIFAT) - 1] == self::FREESECT)
            array_pop($this->DIFAT);
    }
    // So, we done with reading DIFAT. Now chains of FAT sectors should be converted 
    // Lets go further.
    private function readFATChains() {
        // Sector size
        $size = 1 << $this->sectorShift;
        $this->fatChains = array();

        // Going through  DIFAT array.
        for ($i = 0; $i < count($this->DIFAT); $i++) {
            // Go to the sector that we were looking for (with the header)
            $from = ($this->DIFAT[$i] + 1) << $this->sectorShift;
            // Getting the FAT chain: array index is a current sector,
            // value from an array s index of the next element or
            // ENDOFCHAIN - if it is last element in the chain.
            for ($j = 0; $j < $size; $j += 4)
                $this->fatChains[] = $this->getLong($from + $j);
        }
    }
    // We done with reading of FAT sequences. Now heed to read MiniFAT-sequences exaactly the same way.
    private function readMiniFATChains() {
        // Sector size
        $size = 1 << $this->sectorShift;
        $this->miniFATChains = array();

        // Looking for the first sector with MiniFAT- sequences
        $from = $this->fMiniFAT;
        // If MiniFAT appears to be in file then 
        while ($from != self::ENDOFCHAIN) {
            // Looking for the offset to the sector with MiniFat-sequence
            $start = ($from + 1) << $this->sectorShift;
            // Read the sequence from the current sector
            for ($i = 0; $i < $size; $i += 4)
                $this->miniFATChains[] = $this->getLong($start + $i);
            // If this is notthe last sector in the chain we need to move forward
            $from = $this->fatChains[$from];
        }
    }

    // The most important functions that reads structure of "files" of such a type 
    // All the FS objects are written into this structure.
    private function readDirectoryStructure() {
        // get the 1st sector with "files" in file system
        $from = $this->fDir;
        // Get the sector size
        $size = 1 << $this->sectorShift;
        $this->fatEntries = array();
        do {
            // get sector in the file
            $start = ($from + 1) << $this->sectorShift;
            // Let go through the content of this sector. One sector contains up to 4  (or 128 for version 4)
            // entries to FS. Lets read them.
            for ($i = 0; $i < $size; $i += 128) {
                // Get the binary data
                $entry = substr($this->data, $start + $i, 128);
                // and prcess these data:
                $this->fatEntries[] = array(
                    // get the entry name
                    "name" => $this->utf16_to_ansi(substr($entry, 0, $this->getShort(0x40, $entry))),
                    // and its type: either stream, or user data, or empty sector, etc.
                    "type" => ord($entry[0x42]),
                    // its color in the Red-Black tree
                    "color" => ord($entry[0x43]),
                    // its "left" siblings
                    "left" => $this->getLong(0x44, $entry),
                    // its "right" siblings
                    "right" => $this->getLong(0x48, $entry),
                    // its child
                    "child" => $this->getLong(0x4C, $entry),
                    // offset to the content in FAT or miniFAT
                    "start" => $this->getLong(0x74, $entry),
                    // size of the content
                    "size" => $this->getSomeBytes($entry, 0x78, 8),
                );
            }

            // get the next sector with descriptions and jump there
            $from = $this->fatChains[$from];
            // Of course if such a sector exists
        } while ($from != self::ENDOFCHAIN);

        // remove "empty" entries  at the end if any.
        while($this->fatEntries[count($this->fatEntries) - 1]["type"] == 0)
            array_pop($this->fatEntries);
    }

    // Support function to get the adequate name of the current entrie in FS.
    // Note: names are written in the Unicode.
    private function utf16_to_ansi($in) {
        $out = "";
        for ($i = 0; $i < strlen($in); $i += 2)
            $out .= chr($this->getShort($i, $in));
        return trim($out);
    }

    protected function unicode_to_utf8($in, $check = false) { 
        $out = ""; 
        if ($check && strpos($in, chr(0)) !== 1) { 
            while (($i = strpos($in, chr(0x13))) !== false) { 
                $j = strpos($in, chr(0x15), $i + 1); 
                if ($j === false) 
                    break; 

                $in = substr_replace($in, "", $i, $j - $i); 
            } 
            for ($i = 0; $i < strlen($in); $i++) { 
                if (ord($in[$i]) >= 32) {} 
                elseif ($in[$i] == ' ' || $in[$i] == '\n') {} 
                else 
                    $in = substr_replace($in, "", $i, 1); 
            } 
            $in = str_replace(chr(0), "", $in); 

            return $in; 
        } elseif ($check) { 
            while (($i = strpos($in, chr(0x13).chr(0))) !== false) { 
                $j = strpos($in, chr(0x15).chr(0), $i + 1); 
                if ($j === false) 
                    break; 

                $in = substr_replace($in, "", $i, $j - $i); 
            } 
            $in = str_replace(chr(0).chr(0), "", $in); 
        } 

        // Loop thriugh 2 byte words
        $skip = false; 
        for ($i = 0; $i < strlen($in); $i += 2) { 
            $cd = substr($in, $i, 2); 
            if ($skip) { 
                if (ord($cd[1]) == 0x15 || ord($cd[0]) == 0x15) 
                    $skip = false; 
                continue; 
            } 

            // If upper byte is  0 then this is ANSI
           if (ord($cd[1]) == 0) { 
                // If ASCII value is higher than 32 we will write it as it is. 
                if (ord($cd[0]) >= 32) 
                    $out .= $cd[0]; 
                elseif ($cd[0] == ' ' || $cd[0] == '\n') 
                    $out .= $cd[0]; 
                elseif (ord($cd[0]) == 0x13) 
                    $skip = true; 
                else { 
                    continue; 
                    // В противном случае проверяем символы на внедрённые команды (список можно 
                    // дополнить и пополнить). 
                    switch (ord($cd[0])) { 
                        case 0x0D: case 0x07: $out .= "\n"; break; 
                        case 0x08: case 0x01: $out .= ""; break; 
                        case 0x13: $out .= "HYPER13"; break; 
                        case 0x14: $out .= "HYPER14"; break; 
                        case 0x15: $out .= "HYPER15"; break; 
                        default: $out .= " "; break; 
                    } 
                } 
            } else { // Иначе преобразовываем в HTML entity 
                if (ord($cd[1]) == 0x13) { 
                    echo "@"; 
                    $skip = true; 
                    continue; 
                } 
                $out .= "&#x".sprintf("%04x", $this->getShort(0, $cd)).";"; 
            } 
        } 

        // and return the results
        return $out; 
    } 

    // Support function to geto some bytes from the string
    // taking into account order of bytes and converting values into a number.
    protected function getSomeBytes($data, $from, $count) {
        // Read data from  $data by default.
        if ($data === null)
            $data = $this->data;

        // Read a piece
        $string = substr($data, $from, $count);
        // in case of backward order reverse it
        if ($this->isLittleEndian)
            $string = strrev($string);

        // encode from binary to hex and to a number.
        return hexdec(bin2hex($string));
    }
    // Read a word from the variable (by default from this->data)
    protected function getShort($from, $data = null) {
        return $this->getSomeBytes($data, $from, 2);
    }
    // read a double word from the variable (by default from this->data)
    protected function getLong($from, $data = null) {
        return $this->getSomeBytes($data, $from, 4);
    }
} 
// Reading text from DOC
// Версия 0.4
// Author: Алексей Рембиш a.k.a Ramon
// E-mail: 				
// Copyright 2009
// Comments translated by Sergey Butakov


// Class to work with Microsoft Word Document (or just doc). It extends 
// Windows Compound Binary File Format. Lets try to find text here

class doc extends cfb {
    // This function extends parse funciton and returns text from the file. 
    // If returns flase if something went wrong.
    public function parse() {
        parent::parse();

        // To read a DOC file we need 2 streams - WordDocument and 0Table or
        // 1Table depending on the situation. Lets get hte first stream. 
        // It contains pieces of text we need to collect.
        $wdStreamID = $this->getStreamIdByName("WordDocument");
        if ($wdStreamID === false) { return false; }

        // We got the stream. Lets read it into a variable
        $wdStream = $this->getStreamById($wdStreamID);

        // Next we need to get something from  FIB - special block named
        // File Information Block that is located in the beginning of WordDocument stream.
        $bytes = $this->getShort(0x000A, $wdStream);
        				
        // Read which table we need to read: number 0 or number 1.
        // To do so we need to read a small bit from the header.
        $fWhichTblStm = ($bytes & 0x0200) == 0x0200;

        //Now we need to get the position of  CLX in the table stream. And the size of CLX itself.
        $fcClx = $this->getLong(0x01A2, $wdStream);
        $lcbClx = $this->getLong(0x01A6, $wdStream);

        // Conting few values to separate positions from the size in  clx
        $ccpText = $this->getLong(0x004C, $wdStream);
        $ccpFtn = $this->getLong(0x0050, $wdStream);
        $ccpHdd = $this->getLong(0x0054, $wdStream);
        $ccpMcr = $this->getLong(0x0058, $wdStream);
        $ccpAtn = $this->getLong(0x005C, $wdStream);
        $ccpEdn = $this->getLong(0x0060, $wdStream);
        $ccpTxbx = $this->getLong(0x0064, $wdStream);
        $ccpHdrTxbx = $this->getLong(0x0068, $wdStream);

        // Using the value that we just got we can look for the value of the last CP - character position
        $lastCP = $ccpFtn + $ccpHdd + $ccpMcr + $ccpAtn + $ccpEdn + $ccpTxbx + $ccpHdrTxbx;
        $lastCP += ($lastCP != 0) + $ccpText;

        // Get the required table in the file.
        $tStreamID = $this->getStreamIdByName(intval($fWhichTblStm)."Table");
        if ($tStreamID === false) { return false; }

        // And read the stream to a variable
        $tStream = $this->getStreamById($tStreamID);
        // Потом находим в потоке CLX
        $clx = substr($tStream, $fcClx, $lcbClx);

        // Now we need to go through  CLX (yes... its complex) looking for piece with offsets and sizes of text pieces
        $lcbPieceTable = 0;
        $pieceTable = "";

        // Well... this is the most exciting part. There is not too much of documentation on the web site about  
        // what can be found before  pieceTable in the  CLX. So we will do the total search looking 
        // for the possible beginning of pieceTable (it must start with  0х02), and read the following 4 bytes
        // - size of pieceTable. If the actual size equial to size writtent in the offset then Bingo! we found pieceTable. 
        // If not continue the search.

        $from = 0;
        // Looking for  0х02 in CLX starting from the current offset
        while (($i = strpos($clx, chr(0x02), $from)) !== false) {
            // Get the pieceTable size
            $lcbPieceTable = $this->getLong($i + 1, $clx);
            // Get the  pieceTable
            $pieceTable = substr($clx, $i + 5);

            // If the real size differs from required then this is not what we are lloking for.
            // Skip it.
            if (strlen($pieceTable) != $lcbPieceTable) {
                $from = $i + 1;
                continue;
            }
            // Oh.... we got it!!! its break time  my littel friends!
            break;
        }

        // Now we need to fill the array of  character positions, until we got the last  CP.
        $cp = array(); $i = 0;
        while (($cp[] = $this->getLong($i, $pieceTable)) != $lastCP)
            $i += 4;
        // The rest will go as PCD (piece descriptors)
        $pcd = str_split(substr($pieceTable, $i + 4), 8);

        $text = "";
        // Yes! we came to our main goal - reading text from file.
        // Go through the descriptors of such pieces
        for ($i = 0; $i < count($pcd); $i++) {
            // Get the word with offset and  compression flag
            $fcValue = $this->getLong(2, $pcd[$i]);
            // Check what do we have: simple ANSI or Unicode
            $isANSI = ($fcValue & 0x40000000) == 0x40000000;
            // The rest without top will go as an offset
            $fc = $fcValue & 0x3FFFFFFF;

            // Get the piece of text
            $lcb = $cp[$i + 1] - $cp[$i];
            // if htis is Unicode, then lets read twice more bytes.
            if (!$isANSI)
                $lcb *= 2;
            // If ANSI - start twice earlier.
            else
                $fc /= 2;

            // Read a piece from Worddocument stream considering the offset
            $part = substr($wdStream, $fc, $lcb);
            // If this is a Unicode text then decode it to the regular text
            if (!$isANSI)
                $part = $this->unicode_to_utf8($part);

            // add a piece
            $text .= $part;
        }

        // Remove entries with embedded objects from the file
        $text = preg_replace("/HYPER13 *(INCLUDEPICTURE|HTMLCONTROL)(.*)HYPER15/iU", "", $text);
        $text = preg_replace("/HYPER13(.*)HYPER14(.*)HYPER15/iU", "$2", $text);
        // Return the results
        return $text;
    }
    // Function to convert from Unicode to UTF8
    protected function unicode_to_utf8($in) {
        $out = "";
        // Loop through 2-byte sequences
        for ($i = 0; $i < strlen($in); $i += 2) {
            $cd = substr($in, $i, 2);

            // If the first byte is 0 then this is  ANSI
            if (ord($cd[1]) == 0) {
                // If ASCII value of the low byte is higher than 32 then write it as it is.
                if (ord($cd[0]) >= 32)
                    $out .= $cd[0];

                // Otherwise check symbols against embedded commands. Please extend the list ;)
                switch (ord($cd[0])) {
                    case 0x0D: case 0x07: $out .= "\n"; break;
                    case 0x08: case 0x01: $out .= ""; break;
                    case 0x13: $out .= "HYPER13"; break;
                    case 0x14: $out .= "HYPER14"; break;
                    case 0x15: $out .= "HYPER15"; break;
                }
            } else // Otherwise convert to  HTML entity
                $out .= html_entity_decode("&#x".sprintf("%04x", $this->getShort(0, $cd)).";");
        }

        // And... return the result
        return $out;
    }
}

// Function to convert doc to plain-text. For those who "don't need classes".
function doc2text($filename) {
    $doc = new doc;
    $doc->read($filename);
    return $doc->parse();
}

// Reading text from PPT
// Version 0.3
// Auhtor: Алексей Рембиш a.k.a Ramon
// E-mail: alex@rembish.ru
// Copyright 2009
// Comments translated by Sergey

class ppt extends cfb {
    public function parse() {
        parent::parse();

        // File must have  Current User stream.
        $cuStreamID = $this->getStreamIdByName("Current User");
        if ($cuStreamID === false) { return false; }

        // Get this stream and check hash (do we really have PowerPoint-presentation?) 
        // and read the offset to the first ocurence of UserEditAtom
        $cuStream = $this->getStreamById($cuStreamID);
        if ($this->getLong(12, $cuStream) == 0xF3D1C4DF) { return false; }
        $offsetToCurrentEdit = $this->getLong(16, $cuStream);

        // Getting stream named PowerPoint Document.
        $ppdStreamID = $this->getStreamIdByName("PowerPoint Document");
        if ($ppdStreamID === false) { return false; }
        $ppdStream = $this->getStreamById($ppdStreamID);

        // Look for all UserEditAtoms in PPT document. We need UserEditAtoms to get offsets to PersistDirectory.
        $offsetLastEdit = $offsetToCurrentEdit;
        $persistDirEntry = array();
        $live = null;
        $offsetPersistDirectory = array();
        do {
            $userEditAtom = $this->getRecord($ppdStream, $offsetLastEdit, 0x0FF5);
            $live = &$userEditAtom;
            array_unshift($offsetPersistDirectory, $this->getLong(12, $userEditAtom));
            $offsetLastEdit = $this->getLong(8, $userEditAtom);
        } while ($offsetLastEdit != 0x00000000);

        // Looping through all the offsets.
        for ($j = 0; $j < count($offsetPersistDirectory); $j++) {
            $rgPersistDirEntry = $this->getRecord($ppdStream, $offsetPersistDirectory[$j], 0x1772);
            if ($rgPersistDirEntry === false) { return false; }

            // Read 4-byte words: first 20 bit represent the initial ID of this entry in PersistDirectory,
            // next 12 bytes - number of subsequent offsets				.
            for ($k = 0; $k < strlen($rgPersistDirEntry); ) {
                $persist = $this->getLong($k, $rgPersistDirEntry);
                $persistId = $persist & 0x000FFFFF;
                $cPersist = (($persist & 0xFFF00000) >> 20) & 0x00000FFF;
                $k += 4;

                // Based on the results we got we need to populate PersistDirectory array.
                for ($i = 0; $i < $cPersist; $i++) {
                    $offset = $this->getLong($k + $i * 4, $rgPersistDirEntry);
                    $persistDirEntry[$persistId + $i] = $this->getLong($k + $i * 4, $rgPersistDirEntry);
                }
                $k += $cPersist * 4;
            }
        }

        // In the last record we need to fined the ID of the entry with DocumentContainer.
        $docPersistIdRef = $this->getLong(16, $live);
        $documentContainer = $this->getRecord($ppdStream, $persistDirEntry[$docPersistIdRef], 0x03E8);

        // No we need to skip a lot of gqarbage to SlideList.
        $offset = 40 + 8;
        $exObjList = $this->getRecord($documentContainer, $offset, 0x0409);
        if ($exObjList) $offset += strlen($exObjList) + 8;
        $documentTextInfo = $this->getRecord($documentContainer, $offset, 0x03F2);
        $offset += strlen($documentTextInfo) + 8;
        $soundCollection = $this->getRecord($documentContainer, $offset, 0x07E4);
        if ($soundCollection) $offset += strlen($soundCollection) + 8;
        $drawingGroup = $this->getRecord($documentContainer, $offset, 0x040B);
        $offset += strlen($drawingGroup) + 8;
        $masterList = $this->getRecord($documentContainer, $offset, 0x0FF0);
        $offset += strlen($masterList) + 8;
        $docInfoList = $this->getRecord($documentContainer, $offset, 0x07D0);
        if ($docInfoList) $offset += strlen($docInfoList) + 8;
        $slideHF = $this->getRecord($documentContainer, $offset, 0x0FD9);
        if ($slideHF) $offset += strlen($slideHF) + 8;
        $notesHF = $this->getRecord($documentContainer, $offset, 0x0FD9);
        if ($notesHF) $offset += strlen($notesHF) + 8;

        // Clean up the garbage.
        unset($exObjList, $documentTextInfo, $soundCollection, $drawingGroup, $masterList, $docInfoList, $slideHF, $notesHF);

        // Reading SlideList structure.
        $slideList = $this->getRecord($documentContainer, $offset, 0x0FF0);
        $out = "";
        for ($i = 0; $i < strlen($slideList); ) {
            // Read the current block and use its type to decide how to process it.
            $block = $this->getRecord($slideList, $i);
            switch($this->getRecordType($slideList, $i)) {
                case 0x03F3: # RT_SlidePersistAtom
                    // The worst case: we have pointer to a slide. If this is the case 
                    // we have to get this slide from PersistDirectory.
                    $pid = $this->getLong(0, $block);
                    $slide = $this->getRecord($ppdStream, @$persistDirEntry[$pid], 0x03EE);

                    // Again skip lots of different stuff looking for Drawing structure.
                    $offset = 32;
                    $slideShowSlideInfoAtom = $this->getRecord($slide, $offset, 0x03F9);
                    if ($slideShowSlideInfoAtom) $offset += strlen($slideShowSlideInfoAtom) + 8;
                    $perSlideHFContainer = $this->getRecord($slide, $offset, 0x0FD9);
                    if ($perSlideHFContainer) $offset += strlen($perSlideHFContainer) + 8;
                    $rtSlideSyncInfo12 = $this->getRecord($slide, $offset, 0x3714);
                    if ($rtSlideSyncInfo12) $offset += strlen($rtSlideSyncInfo12) + 8;

                    // Drawing is MS Drawing object that has header structure similar to PPT .
                    // To avoid possible parsing of complicated nested structures we can search the text directly.
                    $drawing = $this->getRecord($slide, $offset, 0x040C);
                    $from = 0;
                    while(preg_match("#(\xA8|\xA0)\x0F#", $drawing, $pocket, PREG_OFFSET_CAPTURE, $from)) {
                        $pocket = @$pocket[1];
                        // We must check that block header starts with 00, otherwise it may happen that we found
                        //  something in the middle of other data 
                        if (substr($drawing, $pocket[1] - 2, 2) == "\x00\x00") {
                            // Read either plain or Unicode text.
                            if (ord($pocket[0]) == 0xA8)
                                $out .= htmlspecialchars($this->getRecord($drawing, $pocket[1] - 2, 0x0FA8))." ";
                            else
                                $out .= $this->unicode_to_utf8($this->getRecord($drawing, $pocket[1] - 2, 0x0FA0))." ";
                        }
                        // Read the next entry
                        $from = $pocket[1] + 2;
                    }
                break;
                case 0x0FA0: # RT_TextCharsAtom
                // Simple option: we've found Unicode-text
                    $out .= $this->unicode_to_utf8($block)." ";
                break;
                case 0x0FA8: # RT_TextBytesAtom
                // Or regular plain text.
                    $out .= htmlspecialchars($block)." ";
                break;
                # skip other "options"
            }

            // Move by the length of the block with header.
            $i += strlen($block) + 8;
        }

        // Return UTF-8 text.
        return html_entity_decode(iconv("windows-1251", "utf-8", $out), ENT_QUOTES, "UTF-8");
    }

    // Additional funciton that defines the lingth of the current internal structure.
    // It gets the input stream, offset and type of the structure to read. 
    // Type will be used ot check the actual structure we read.
    private function getRecordLength($stream, $offset, $recType = null) {
        $rh = substr($stream, $offset, 8);
        if (!is_null($recType) && $recType != $this->getShort(2, $rh))
            return false;
        return $this->getLong(4, $rh);
    }
    // Get the type of the current structure according to MS manual.
    private function getRecordType($stream, $offset) {
        $rh = substr($stream, $offset, 8);
        return $this->getShort(2, $rh);
    }
    // Get the record by its offset. Attention, the header doesn't go back
    
    private function getRecord($stream, $offset, $recType = null) {
        $length = $this->getRecordLength($stream, $offset, $recType);
        if ($length === false)
            return false;
        return substr($stream, $offset + 8, $length);
    }
}

// For those ones who do not need classes :)
function ppt2text($filename) {
    $ppt = new ppt;
    $ppt->read($filename);
    return $ppt->parse();
} 

?>
