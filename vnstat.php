<?php
    //
    // vnStat PHP frontend (c)2006-2010 Bjorge Dijkstra (bjd@jooz.net)
    //
    // This program is free software; you can redistribute it and/or modify
    // it under the terms of the GNU General Public License as published by
    // the Free Software Foundation; either version 2 of the License, or
    // (at your option) any later version.
    //
    // This program is distributed in the hope that it will be useful,
    // but WITHOUT ANY WARRANTY; without even the implied warranty of
    // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    // GNU General Public License for more details.
    //
    // You should have received a copy of the GNU General Public License
    // along with this program; if not, write to the Free Software
    // Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    //
    //
    // see file COPYING or at http://www.gnu.org/licenses/gpl.html
    // for more information.
    //

    //
    // Valid values for other parameters you can pass to the script.
    // Input parameters will always be limited to one of the values listed here.
    // If a parameter is not provided or invalid it will revert to the default,
    // the first parameter in the list.
    //
    if (isset($_SERVER['PHP_SELF']))
    {
	$script = $_SERVER['PHP_SELF'];
    }
    elseif (isset($_SERVER['SCRIPT_NAME']))
    {
	$script = $_SERVER['SCRIPT_NAME'];
    }
    else
    {
	die('can\'t determine script name!');
    }

    $page_list  = array('s','h','d','m');

    $graph_list = array('large','small','none');

    $page_title['s'] = T('summary');
    $page_title['h'] = T('hours');
    $page_title['d'] = T('days');
    $page_title['m'] = T('months');


    //
    // functions
    //
    function validate_input()
    {
        global $page,  $page_list;
        global $iface, $iface_list;
        global $graph, $graph_list;
	global $colorscheme, $style;
        //
        // get interface data
        //
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $iface = isset($_GET['if']) ? $_GET['if'] : '';
        $graph = isset($_GET['graph']) ? $_GET['graph'] : '';
        $style = isset($_GET['style']) ? $_GET['style'] : '';

        if (!in_array($page, $page_list))
        {
            $page = $page_list[0];
        }

        if (!in_array($iface, $iface_list))
        {
            $iface = $iface_list[0];
        }

        if (!in_array($graph, $graph_list))
        {
            $graph = $graph_list[0];
        }

	$tp = "./themes/$style";
        if (!is_dir($tp) || !file_exists("$tp/theme.php") || !preg_match('/^[a-z0-9-_]+$/i', $style))
        {
	    $style = DEFAULT_COLORSCHEME;
        }
    }


    function get_vnstat_data($use_label=true)
    {
        global $iface, $vnstat_bin, $data_dir;
        global $hour,$day,$month,$top,$summary;

	$vnstat_data = array();
        if (!isset($vnstat_bin) || $vnstat_bin == '')
        {
	    if (file_exists("$data_dir/vnstat_dump_$iface"))
	    {
        	$vnstat_data = file("$data_dir/vnstat_dump_$iface");
	    }
        }
        else
        {
            $fd = popen("$vnstat_bin --json a -i $iface", "r");
            if (is_resource($fd))
            {
            	$buffer = '';
            	while (!feof($fd)) {
                	$buffer .= fgets($fd);
            	}
            	pclose($fd);
				$vnstat_data = array();
				$obj=json_decode($buffer,true);
				convertFromJson('hour',$obj,$vnstat_data);
				convertFromJson('day',$obj,$vnstat_data);
				convertFromJson('month',$obj,$vnstat_data);
				convertFromJson('top',$obj,$vnstat_data);
            }
        }


        $day = array();
        $hour = array();
        $month = array();
        $top = array();

        if (isset($vnstat_data[0]) && strpos($vnstat_data[0], 'Error') !== false) {
          return;
        }

        //
        // extract data
        //
        foreach($vnstat_data as $line)
        {
            $d = explode(';', trim($line));
            if ($d[0] == 'd')
            {
                $day[$d[1]]['time']  = $d[2];
                $day[$d[1]]['rx']    = $d[3] * 1024 + $d[5];
                $day[$d[1]]['tx']    = $d[4] * 1024 + $d[6];
                $day[$d[1]]['act']   = $d[7];
                if ($d[2] != 0 && $use_label)
                {
                    $day[$d[1]]['label'] = strftime(T('datefmt_days'),$d[2]);
                    $day[$d[1]]['img_label'] = strftime(T('datefmt_days_img'), $d[2]);
                }
                elseif($use_label)
                {
                    $day[$d[1]]['label'] = '';
                    $day[$d[1]]['img_label'] = '';
                }
            }
            else if ($d[0] == 'm')
            {
                $month[$d[1]]['time'] = $d[2];
                $month[$d[1]]['rx']   = $d[3] * 1024 + $d[5];
                $month[$d[1]]['tx']   = $d[4] * 1024 + $d[6];
                $month[$d[1]]['act']  = $d[7];
                if ($d[2] != 0 && $use_label)
                {
                    $month[$d[1]]['label'] = strftime(T('datefmt_months'), $d[2]);
                    $month[$d[1]]['img_label'] = strftime(T('datefmt_months_img'), $d[2]);
                }
                else if ($use_label)
                {
                    $month[$d[1]]['label'] = '';
                    $month[$d[1]]['img_label'] = '';
                }
            }
            else if ($d[0] == 'h')
            {
                $hour[$d[1]]['time'] = $d[2];
                $hour[$d[1]]['rx']   = $d[3];
                $hour[$d[1]]['tx']   = $d[4];
                $hour[$d[1]]['act']  = 1;
                if ($d[2] != 0 && $use_label)
                {
                    $st = $d[2] - ($d[2] % 3600);
                    $et = $st + 3600;
                    $hour[$d[1]]['label'] = strftime(T('datefmt_hours'), $st).' - '.strftime(T('datefmt_hours'), $et);
                    $hour[$d[1]]['img_label'] = strftime(T('datefmt_hours_img'), $d[2]);
                }
                else if ($use_label)
                {
                    $hour[$d[1]]['label'] = '';
                    $hour[$d[1]]['img_label'] = '';
                }
            }
            else if ($d[0] == 't')
            {
                $top[$d[1]]['time'] = $d[2];
                $top[$d[1]]['rx']   = $d[3] * 1024 + $d[5];
                $top[$d[1]]['tx']   = $d[4] * 1024 + $d[6];
                $top[$d[1]]['act']  = $d[7];
                if($use_label)
                {
                    $top[$d[1]]['label'] = strftime(T('datefmt_top'), $d[2]);
                    $top[$d[1]]['img_label'] = '';
                }
            }
            else
            {
                $summary[$d[0]] = isset($d[1]) ? $d[1] : '';
            }
        }

        rsort($day);
        rsort($month);
        rsort($hour);
    }

    function convertFromJson($tP,$json,&$data) {
        $arr=array_reverse($json['interfaces']['0']['traffic'][$tP]);
        $fL=$tP[0];
        $maxIndex=24;
        if ($fL=='d') {
            $maxIndex=30;
        }
        elseif ($fL=='m') {
            $maxIndex=12;
        }
        elseif ($fL=='t') {
            $maxIndex=5;
        }
        for ($i=0; $i< min($maxIndex,count($arr)); $i++) {
            $line=$fL.';'.$i.';'.strtotime(
                ($fL=='h' ? $arr[$i]['time'][$tP] : "00").":".
                ($fL=='h' ? $arr[$i]['time']['minute'] : "00").":00".
                $arr[$i]['date']['month']."/".
                ($fL=='m' ? "01" : $arr[$i]['date']['day'])."/".
                $arr[$i]['date']['year']).";".
                ($fL!='h' ? "0;0;" : "").
                intdiv($arr[$i]['rx'],1024) .";".
                intdiv($arr[$i]['tx'],1024) .
                ($fL!='h' ? ";1" : "");
            $data[]=$line;
        }
    }

?>
