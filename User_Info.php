<?php

/**
 * Class User_Info
 * @package Site
 */
class User_Info {


    private static $languages = [];
    private static $ip_info   = [];
    private static $timezone  = [];

    private static $_windows_version = [
        "10.0" => ["10", "6"],
        "6.4"  => ["10", "6"], // Windows 10 before 10240
        "6.3"  => ["8.1", "5"],
        "6.2"  => ["8", "5"],
        "6.1"  => ["7", "4"],
        "6.0"  => ["Vista", "3"],
        "5.2"  => ["Server 2003", "2"],
        "5.1"  => ["XP", "2"],
        "5.01" => ["2000 Service Pack 1", "1"],
        "5.0"  => ["2000", "1"],
        "4.0"  => ["NT 4.0", "1"],
        "3.51" => ["NT 3.11", "1"],
    ];


    /**
     * @return array
     * @throws \Exception
     */
    public static function getAll() {

        $page_info    = self::getPageInfo();
        $ip_info      = self::getIpInfo();
        $languages    = self::getLanguages();
        $net_type     = self::getNetType();
        $browser_info = self::getBrowser();
        $devise_info  = self::getDevise();
        $os_info      = self::getOs();
        $timezone     = self::getTimezone();

        $user = [
            'page'                 => $page_info['page'],
            'page_referer'         => $page_info['page_referer'],
            'page_prevent'         => $page_info['page_prevent'],
            'ip'                   => $ip_info['ip'],
            'city'                 => $ip_info['city'],
            'region'               => $ip_info['region'],
            'country'              => $ip_info['country'],
            'location'             => $ip_info['location'],
            'location_link'        => $ip_info['location_link'],
            'location_img_300x300' => $ip_info['location_img_300x300'],
            'location_img_600x300' => $ip_info['location_img_600x300'],
            'location_img_900x300' => $ip_info['location_img_900x300'],
            'postal'               => $ip_info['postal'],
            'hostname'             => $ip_info['hostname'],
            'organisation'         => $ip_info['organisation'],
            'browser'              => $browser_info['title'],
            'browser_version'      => $browser_info['version'],
            'browser_is_mobile'    => $browser_info['is_mobile'],
            'browser_screen_size'  => $browser_info['screen_size'],
            'devise_title'         => $devise_info['title'],
            'devise_model'         => $devise_info['model'],
            'devise_brand'         => $devise_info['brand'],
            'devise_cpu_brand'     => $devise_info['cpu_brand'],
            'devise_link'          => $devise_info['link'],
            'os_title'             => $os_info['title'],
            'os_name'              => $os_info['name'],
            'os_version'           => $os_info['version'],
            'os_link'              => $os_info['link'],
            'os_x64'               => $os_info['x64'],
            'net_type'             => $net_type,
            'language_best'        => current($languages),
            'languages'            => $languages,
            'timezone_name'        => $timezone['name'],
            'timezone_offset'      => $timezone['offset'],
        ];


        return $user;
    }


    /**
     * @return array
     */
    public static function getPageInfo() {

        $page_info = [
            'page'         => ! empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'page_referer' => ! empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'page_prevent' => ! empty($_SERVER['HTTP_PREVENT_PAGE']) ? $_SERVER['HTTP_PREVENT_PAGE'] : ''
        ];

        return $page_info;
    }


    /**
     * @param null $ip
     * @return array
     * @throws \Exception
     */
    public static function getIpInfo($ip = null) {

        if ( ! $ip) {
            if ( ! empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];

            } elseif ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }


            if (empty($ip)) {
                return [
                    'ip'                   => null,
                    'city'                 => null,
                    'region'               => null,
                    'country'              => null,
                    'location'             => null,
                    'location_link'        => null,
                    'location_img_300x300' => null,
                    'location_img_600x300' => null,
                    'location_img_900x300' => null,
                    'postal'               => null,
                    'hostname'             => null,
                    'organisation'         => null,
                ];
            }
        }

        if (empty(self::$ip_info[$ip])) {
            $ip_info = self::request('get', "https://ipinfo.io/{$ip}/json");


            if (empty($ip_info['location']) && ( ! empty($ip_info['city']) || ! empty($ip_info['region']))) {
                $find_str = ( ! empty($ip_info['city']) ? $ip_info['city'] : '') . ' ' . ( ! empty($ip_info['region']) ? $ip_info['region'] : '');
                $find_str = urlencode($find_str);
                $geo_info = self::request('get', "https://geocode-maps.yandex.ru/1.x/?geocode={$find_str}&format=json");

                if ( ! empty($geo_info) &&
                    ! empty($geo_info['response']) &&
                    ! empty($geo_info['response']['GeoObjectCollection']) &&
                    ! empty($geo_info['response']['GeoObjectCollection']['featureMember']) &&
                    ! empty($geo_info['response']['GeoObjectCollection']['featureMember'][0]) &&
                    ! empty($geo_info['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']) &&
                    ! empty($geo_info['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']) &&
                    ! empty($geo_info['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'])
                ) {
                    $ip_info['location'] = $geo_info['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
                }
            }

            $location_encode = ! empty($ip_info['location']) ? urlencode(str_replace(' ', ',', $ip_info['location'])) : '';

            self::$ip_info[$ip] = [
                'ip'                   => $ip,
                'city'                 => ! empty($ip_info['city']) ? $ip_info['city'] : '',
                'region'               => ! empty($ip_info['region']) ? $ip_info['region'] : '',
                'country'              => ! empty($ip_info['country']) ? $ip_info['country'] : '',
                'location'             => ! empty($ip_info['location']) ? $ip_info['location'] : '',
                'location_link'        => ! empty($ip_info['location']) ? "https://yandex.ru/maps/?ll={$location_encode}&z=10" : '',
                'location_img_300x300' => ! empty($ip_info['location']) ? "https://static-maps.yandex.ru/1.x/?ll={$location_encode}&z=10&l=map&size=300,300&pt={$location_encode},pm2rdl" : '',
                'location_img_600x300' => ! empty($ip_info['location']) ? "https://static-maps.yandex.ru/1.x/?ll={$location_encode}&z=10&l=map&size=600,300&pt={$location_encode},pm2rdl" : '',
                'location_img_900x300' => ! empty($ip_info['location']) ? "https://static-maps.yandex.ru/1.x/?ll={$location_encode}&z=10&l=map&size=900,300&pt={$location_encode},pm2rdl" : '',
                'postal'               => ! empty($ip_info['postal']) ? $ip_info['postal'] : '',
                'hostname'             => ! empty($ip_info['hostname']) ? $ip_info['hostname'] : '',
                'organisation'         => ! empty($ip_info['org']) ? $ip_info['org'] : ''
            ];
        }


        return self::$ip_info[$ip];
    }


    /**
     * @return array
     */
    public static function getLanguages() {

        if (empty(self::$languages)) {
            if (($accept_lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']))) {
                $list = [];

                if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/', $accept_lang, $list)) {

                    $languages = array_combine($list[1], $list[2]);

                    foreach ($languages as $n => $v) {
                        $languages[$n] = $v ? $v : 1;
                    }

                    arsort($languages, SORT_NUMERIC);


                    self::$languages = [];
                    foreach ($languages as $n => $v) {
                        if (array_search(strtoupper(strtok($n, '-')), self::$languages) === false) {
                            self::$languages[] = strtoupper(strtok($n, '-'));
                        }
                    }
                }

            }
        }

        return self::$languages;
    }


    /**
     * @param null $user_agent
     * @return string|null
     */
    public static function getNetType($user_agent = null) {

        $netType = null;


        if (is_null($user_agent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                return $netType;
            }
        }


        // wechat: "NetType/WIFI"
        if (stripos($user_agent, 'NetType') !== false) {
            if (preg_match('~NetType/(\S+)~', $user_agent, $matches)) {
                $netType = $matches[1] ?: null;
            }

            // aliApp: "AlipayDefined(nt:WIFI,ws:414|672|3.0)"
        } elseif (stripos($user_agent, 'nt:') !== false) {
            if (preg_match('~\(nt:(\S+),~', $user_agent, $matches)) {
                $netType = $matches[1] ?: null;
            }
        }


        return $netType;
    }


    /**
     * @return array
     */
    public static function getTimezone() {

        if (empty(self::$timezone)) {
            self::$timezone['name']   = ! empty($_COOKIE['dtz_name']) ? $_COOKIE['dtz_name'] : '';
            self::$timezone['offset'] = ! empty($_COOKIE['dtz_offset']) ? $_COOKIE['dtz_offset'] : '';
        }

        return self::$timezone;
    }


    /**
     * Parses a user agent string into its important parts
     * @author Jesse G. Donat <donatj@gmail.com>
     * @link https://github.com/donatj/PhpUserAgent
     * @link http://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
     * @param string|null $user_agent User agent string to parse or null. Uses $_SERVER['HTTP_USER_AGENT'] on NULL
     * @return array
     */
    public static function getBrowser($user_agent = null ) {

        $browser_info = [
            'title'       => null,
            'version'     => null,
            'is_mobile'   => null,
            'screen_size' => ! empty($_COOKIE['screen_size']) ? $_COOKIE['screen_size'] : '',
        ];

        if (is_null($user_agent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                return $browser_info;
            }
        }

        if ( ! $user_agent) return $browser_info;


        if (preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|meego.+mobile|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $user_agent) ||
            preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($user_agent, 0, 4))
        ) {
            $browser_info['is_mobile'] = true;
        } else {
            $browser_info['is_mobile'] = false;
        }


        $platform = null;

        if (preg_match('/\((.*?)\)/im', $user_agent, $parent_matches)) {
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)
				(?:\ [^;]*)?
				(?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);

            $priority = [
                'Xbox One',
                'Xbox',
                'Windows Phone',
                'Tizen',
                'Android',
                'FreeBSD',
                'NetBSD',
                'OpenBSD',
                'CrOS',
                'X11'
            ];

            $result['platform'] = array_unique($result['platform']);
            if (count($result['platform']) > 1) {
                if ($keys = array_intersect($priority, $result['platform'])) {
                    $platform = reset($keys);

                } else {
                    $platform = $result['platform'][0];
                }

            } elseif (isset($result['platform'][0])) {
                $platform = $result['platform'][0];
            }
        }

        if ($platform == 'linux-gnu' || $platform == 'X11') {
            $platform = 'Linux';

        } elseif ($platform == 'CrOS') {
            $platform = 'Chrome OS';
        }

        preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
				TizenBrowser|(?:Headless)?Chrome|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|UCBrowser|Puffin|SamsungBrowser|
				Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
				Valve\ Steam\ Tenfoot|
				NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
				(?:\)?;?)
				(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix', $user_agent, $result, PREG_PATTERN_ORDER);

        // If nothing matched, return null (to avoid undefined index errors)
        if ( ! isset($result['browser'][0]) || ! isset($result['version'][0])) {
            if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $user_agent, $result)) {
                return [
                    'title'       => $result['browser'],
                    'version'     => isset($result['version']) ? $result['version'] ?: null : null,
                    'is_mobile'   => $result['is_mobile'],
                    'screen_size' => $result['screen_size'],
                ];
            }

            return $browser_info;
        }

        if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $user_agent, $rv_result)) {
            $rv_result = $rv_result['version'];
        }

        $browser_info['title']   = $result['browser'][0];
        $browser_info['version'] = $result['version'][0];
        $lowerBrowser = array_map('strtolower', $result['browser']);
        $find         = function($search, &$key, &$value = null) use ($lowerBrowser) {

            $search = (array)$search;
            foreach ($search as $val) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if ($xkey !== false) {
                    $value = $val;
                    $key   = $xkey;

                    return true;
                }
            }

            return false;
        };


        $key = 0;
        $val = '';

        if ($browser_info['title'] == 'Iceweasel' || strtolower($browser_info['title']) == 'icecat') {
            $browser_info['title'] = 'Firefox';

        } elseif ($find('Playstation Vita', $key)) {
            $browser_info['title'] = 'Browser';

        } elseif ($find(['Kindle Fire', 'Silk'], $key, $val)) {
            $browser_info['title'] = $val == 'Silk' ? 'Silk' : 'Kindle';

            if ( ! ($browser_info['version'] = $result['version'][$key]) || ! is_numeric($browser_info['version'][0])) {
                $browser_info['version'] = $result['version'][array_search('Version', $result['browser'])];
            }

        } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
            $browser_info['title'] = 'NintendoBrowser';
            $browser_info['version'] = $result['version'][$key];

        } elseif ($find('Kindle', $key, $platform)) {
            $browser_info['title'] = $result['browser'][$key];
            $browser_info['version'] = $result['version'][$key];

        } elseif ($find('OPR', $key)) {
            $browser_info['title'] = 'Opera Next';
            $browser_info['version'] = $result['version'][$key];

        } elseif ($find('Opera', $key, $browser_info['title'])) {
            $find('Version', $key);
            $browser_info['version'] = $result['version'][$key];

        } elseif ($find('Puffin', $key, $browser_info['title'])) {
            $browser_info['version'] = $result['version'][$key];
            if (strlen($browser_info['version']) > 3) {
                $part = substr($browser_info['version'], -2);
                if (ctype_upper($part)) {
                    $browser_info['version'] = substr($browser_info['version'], 0, -2);
                }
            }

        } elseif ($find([
            'IEMobile',
            'Edge',
            'Midori',
            'Vivaldi',
            'SamsungBrowser',
            'Valve Steam Tenfoot',
            'Chrome',
            'HeadlessChrome'
        ], $key, $browser_info['title'])) {
            $browser_info['version'] = $result['version'][$key];

        } elseif ($rv_result && $find('Trident', $key)) {
            $browser_info['title'] = 'MSIE';
            $browser_info['version'] = $rv_result;

        } elseif ($find('UCBrowser', $key)) {
            $browser_info['title'] = 'UC Browser';
            $browser_info['version'] = $result['version'][$key];

        } elseif ($find('CriOS', $key)) {
            $browser_info['title'] = 'Chrome';
            $browser_info['version'] = $result['version'][$key];

        } elseif ($browser_info['title'] == 'AppleWebKit') {
            if ($platform == 'Android') {
                $browser_info['title'] = 'Android Browser';

            } elseif (strpos($platform, 'BB') === 0) {
                $browser_info['title']  = 'BlackBerry Browser';

            } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
                $browser_info['title'] = 'BlackBerry Browser';

            } else {
                $find('Safari', $key, $browser_info['title']) || $find('TizenBrowser', $key, $browser_info['title']);
            }

            $find('Version', $key);
            $browser_info['version'] = $result['version'][$key];

        } elseif ($pKey = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser']))) {
            $browser_info['title'] = 'NetFront';
        }

        return $browser_info;
    }


    /**
     * Detect $console or Mobile Device
     * @author zsx <zsx@zsxsoft.com>
     * @author Kyle Baker <kyleabaker@gmail.com>
     * @author Fernando Briano <transformers.es@gmail.com>
     * @copyright Copyright 2014-2017 zsx
     * @copyright Copyright 2008-2014 Kyle Baker (email: kyleabaker@gmail.com)
     * @copyright 2008 Fernando Briano (email : transformers.es@gmail.com)
     * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
     * @param $user_agent
     * @return array
     */
    public static function getDevise($user_agent = null) {

        $link  = '';
        $brand = '';
        $model = '';


        if (is_null($user_agent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                return [
                    'title'     => $brand . ($model == '' ? '' : ' ' . $model),
                    'model'     => $model,
                    'brand'     => $brand,
                    'cpu_brand' => null,
                    'link'      => $link,
                ];
            }
        }

        // meizu
        if (preg_match('/MEIZU[ _-](MX|M9)|MX[0-9]{0,1}[; ]|M0(4|5)\d|M35\d|M\d note/i', $user_agent)) {
            $link      = "http://www.meizu.com/";
            $brand     = "Meizu";
            if (preg_match('/(M35[0-9]+)|(M04\d)|(M05\d)/i', $user_agent, $reg_match)) {
                $model = $reg_match[count($reg_match) - 1];
            } elseif (preg_match('/(MX[0-9]{0,1})/i', $user_agent, $reg_match)) {
                $model = $reg_match[count($reg_match) - 1];
            } elseif (preg_match('/(m\d Note)/i', $user_agent, $reg_match)) {
                $model = $reg_match[count($reg_match) - 1];
            }

        // xiaomi
        } elseif (preg_match('/MI-ONE|MI[ -]\d/i', $user_agent)) {
            $link  = "http://www.xiaomi.com/";
            $brand = "Xiaomi";
            if (preg_match('/HM NOTE ([A-Z0-9]+)/i', $user_agent, $reg_match)) {
                $model = "HM-NOTE " . $reg_match[1];
            } elseif (preg_match('/MI-ONE/i', $user_agent, $reg_match)) {
                $model = "1";
            } elseif (preg_match('/MI[ -]([A-Z0-9]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // redmi
        } elseif (preg_match('/HM NOTE|HM \d|Redmi/i', $user_agent)) {
            $link  = "http://www.xiaomi.com/";
            $brand = "Redmi";
            if (preg_match('/HM NOTE ([A-Z0-9]+)/i', $user_agent, $reg_match)) {
                $model = "Note " . $reg_match[1];
            } elseif (preg_match('/HM ([A-Z0-9]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            } elseif (preg_match('/RedMi Note ([A-Z0-9]+)/i', $user_agent, $reg_match)) {
                $model = "Note " . $reg_match[1];
            }

        // BlackBerry
        } elseif (preg_match('/BlackBerry/i', $user_agent)) {
            $link  = "http://www.blackberry.com/";
            $brand = "BlackBerry";
            if (preg_match('/blackberry ?([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // Coolpad
        } elseif (preg_match('/Coolpad/i', $user_agent)) {
            $link  = "http://www.coolpad.com/";
            $brand = "CoolPad";
            if (preg_match('/CoolPad( |\_)?([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[2];
            }

        // Dell
        } elseif (preg_match('/Dell Streak/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Dell_Streak";
            $brand     = "Dell Streak";
        } elseif (preg_match('/Dell/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Dell";
            $brand     = "Dell";

        // Google TV
        } elseif (preg_match('/Google ?TV/i', $user_agent)) {
            $link      = "https://www.android.com/tv/";
            $brand     = "Google TV";

        // Hisense
        } elseif (preg_match('/Hasee/i', $user_agent)) {
            $link      = "http://www.hasee.com/";
            $brand     = "Hasee";
            if (preg_match('/Hasee (([^;\/]+) Build|([^;\/)]+)[);\/ ])/i', $user_agent, $reg_match)) {
                $model = $reg_match[2];
            }

        // HTC
        } elseif (preg_match('/Desire/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/HTC_Desire";
            $brand     = "HTC Desire";
        } elseif (preg_match('/Rhodium/i', $user_agent) || preg_match('/HTC[_|\ ]Touch[_|\ ]Pro2/i', $user_agent) || preg_match('/WMD-50433/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/HTC_Touch_Pro2";
            $brand     = "HTC Touch Pro2";
        } elseif (preg_match('/HTC[_|\ ]Touch[_|\ ]Pro/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/HTC_Touch_Pro";
            $brand     = "HTC Touch Pro";
        } elseif (preg_match('/Windows Phone .+ by HTC/i', $user_agent)) {
            $link  = "http://en.wikipedia.org/wiki/High_Tech_Computer_Corporation";
            $brand = "HTC";
            if (preg_match('/Windows Phone ([0-9A-Za-z]+) by HTC/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }
        } elseif (preg_match('/HTC/i', $user_agent)) {
            $link  = "http://en.wikipedia.org/wiki/High_Tech_Computer_Corporation";
            $brand = "HTC";
            if (preg_match('/HTC[\ |_|-]?([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            } elseif (preg_match('/HTC([._0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model .= str_replace("_", " ", $reg_match[1]);
            }

        // Hisense
        } elseif (preg_match('/Hisense|HS-(?:U|EG?|I|T|X)[0-9]+[a-z0-9\-]*/i', $user_agent)) {
            $link      = "http://www.hisense.com/";
            $brand     = "Hisense";
            if (preg_match('/(HS-(?:U|EG?|I|T|X)[0-9]+[a-z0-9\-]*)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // huawei
        } elseif (preg_match('/Huawei/i', $user_agent)) {
            $link      = "http://www.huawei.com/cn/";
            $brand     = "Huawei";
            if (preg_match('/HUAWEI( |\_)?([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[2];
            }
        } elseif (preg_match('/HONOR[\ ]?([A-Za-z0-9]{3,4}\-[A-Za-z0-9]{3,4})|(Che[0-9]{1}-[a-zA-Z0-9]{4})/i', $user_agent, $reg_match)) {
            $link      = "http://www.huawei.com/cn/";
            $brand     = "Huawei";
            $model     = $reg_match[count($reg_match) - 1];
        } elseif (preg_match('/(H60-L\d+)/i', $user_agent, $reg_match)) {
            $link      = "http://www.huawei.com/cn/";
            $brand     = "Huawei";
            $model     = $reg_match[count($reg_match) - 1];

        // Kindle
        } elseif (preg_match('/Kindle/i', $user_agent)) {
            $link  = "http://en.wikipedia.org/wiki/Amazon_Kindle";
            $brand = "Kindle";
            if (preg_match('/Kindle\/([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // K-Touch
        } elseif (preg_match('/k-touch/i', $user_agent)) {
            $link      = "http://www.k-touch.cn/";
            $brand     = "K-Touch";
            if (preg_match('/k-touch[ _]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // Lenovo
        } elseif (preg_match('/Lenovo|lepad|Yoga/i', $user_agent)) {
            $link  = "http://www.lenovo.com.cn";
            $brand = "Lenovo";
            if (preg_match('/Lenovo[\ |\-|\/|\_]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            } elseif (preg_match('/Yoga( Tablet)?[\ |\-|\/|\_]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = "Yoga " . $reg_match[2];
            } elseif (preg_match('/lepad/i', $user_agent)) {
                $model = 'LePad';
            }

        // Letv
        } elseif (preg_match('/Letv/i', $user_agent)) {
            $link  = "http://www.letv.com";
            $brand = "Letv";
            if (preg_match('/Letv?([- \/])([0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[2];
            }

            // LG
        } elseif (preg_match('/LG/i', $user_agent)) {
            $link  = "http://www.lgmobile.com";
            $brand = "LG";
            if (preg_match('/LGE?([- \/])([0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[2];
            }

        // Motorola
        } elseif (preg_match('/\ Droid/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Motorola_Droid";
            $brand     = "Motorola";
            $model     = "Droid";
        } elseif (preg_match('/XT720/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Motorola";
            $brand     = "Motorola";
            $model     = "XT720";
        } elseif (preg_match('/MOT-/i', $user_agent) || preg_match('/MIB/i', $user_agent)) {
            $link  = "http://en.wikipedia.org/wiki/Motorola";
            $brand = "Motorola";
            if (preg_match('/MOTO([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }
            if (preg_match('/MOT-([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }
        } elseif (preg_match('/XOOM/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Motorola_Xoom";
            $brand     = "Motorola";
            $model     = "Xoom";

        // Microsoft
        } elseif (preg_match('/Microsoft/i', $user_agent)) {
            $link      = "http://www.microsoft.com/";
            $brand     = "Microsoft";
            if (preg_match('/Lumia ([0-9]+)/i', $user_agent, $reg_match)) {
                $model = "Lumia " . $reg_match[1];
            }

        // Nintendo
        } elseif (preg_match('/Nintendo/i', $user_agent)) {
            $brand     = "Nintendo";
            $link      = "http://www.nintendo.com/";
            if (preg_match('/Nintendo DSi/i', $user_agent)) {
                $link      = "http://www.nintendodsi.com/";
                $model     = "DSi";
            } elseif (preg_match('/Nintendo DS/i', $user_agent)) {
                $link      = "http://www.nintendo.com/ds";
                $model     = "DS";
            } elseif (preg_match('/Nintendo WiiU/i', $user_agent)) {
                $link      = "http://www.nintendo.com/wiiu";
                $model     = "Wii U";
            } elseif (preg_match('/Nintendo Wii/i', $user_agent)) {
                $link      = "http://www.nintendo.com/wii";
                $model     = "Wii";
            }

        // Nokia
        } elseif (preg_match('/Nokia/i', $user_agent)) {
            $link      = "http://www.nokia.com/";
            $brand     = "Nokia";
            if (preg_match('/Nokia(E|N| )?([0-9]+)/i', $user_agent, $reg_match)) {
                if (preg_match('/IEMobile|WPDesktop|Edge/i', $user_agent)) {
                    // Nokia Windows Phone
                    if ($reg_match[2] == '909') {
                        $reg_match[2] = '1020';
                    }
                    // Lumia 1020
                    $model = "Lumia " . $reg_match[2];
                } else {
                    $model = $reg_match[1] . $reg_match[2];
                }
            } elseif (preg_match('/Lumia ([0-9]+)/i', $user_agent, $reg_match)) {
                $model = "Lumia " . $reg_match[1];
            }

        // Onda
        } elseif (preg_match('/onda/i', $user_agent)) {
            $link      = "http://www.onda.cn/";
            $brand     = "Onda";

        // Oppo
        } elseif (preg_match('/oppo/i', $user_agent)) {
            $link      = "http://www.oppo.com/";
            $brand     = "OPPO";

        // Oneplus
        } elseif (preg_match('/A0001|A2005|A3000|E1003|One [A-Z]\d{4}/i', $user_agent)) {
            $link      = "http://www.oneplus.cn/";
            $brand     = "OnePlus";
            if (preg_match('/A0001/i', $user_agent)) {
                $model = "1";
            } elseif (preg_match('/A2005/i', $user_agent)) {
                $model = "2";
            } elseif (preg_match('/E1003/i', $user_agent)) {
                $model = "X";
            } elseif (preg_match('/A3000/i', $user_agent)) {
                $model = "3";
            }

        // Palm
        } elseif (preg_match('/\ Pixi\//i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Palm_Pixi";
            $brand     = "Palm Pixi";
        } elseif (preg_match('/\ Pre\//i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Palm_Pre";
            $brand     = "Palm Pre";
        } elseif (preg_match('/Palm/i', $user_agent)) {
            $link      = "http://www.palm.com/";
            $brand     = "Palm";
        } elseif (preg_match('/webos/i', $user_agent)) {
            $link      = "http://www.palm.com/";
            $brand     = "Palm";

        // Playstation
        } elseif (preg_match('/PlayStation/i', $user_agent)) {
            $brand = "PlayStation";
            if (preg_match('/[PS|PlayStation\ ]3/i', $user_agent)) {
                $link  = "http://www.us.playstation.com/PS3";
                $model = "3";
            } elseif (preg_match('/[PS|PlayStation\ ]4/i', $user_agent)) {
                $link  = "http://www.us.playstation.com/PS4";
                $model = "4";
            } elseif (preg_match('/PlayStation Portable|PSP/i', $user_agent)) {
                $link  = "http://www.us.playstation.com/PSP";
                $model = "Portable";
            } elseif (preg_match('/PlayStation Vita|PSVita/i', $user_agent)) {
                $link  = "http://us.playstation.com/psvita/";
                $model = "Vita";
            } else {
                $link = "http://www.us.playstation.com/";
            }

        // Samsung
        } elseif (preg_match('/Galaxy Nexus/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Galaxy_Nexus";
            $brand     = "Galaxy Nexus";
        } elseif (preg_match('/Smart-?TV/i', $user_agent)) {
            $link      = "http://www.samsung.com/us/experience/smart-tv/";
            $brand     = "Samsung Smart TV";
        } elseif (preg_match('/Samsung|SM-|GT-|SCH-|SHV-/i', $user_agent)) {
            $link  = "http://www.samsungmobile.com/";
            $brand = "Samsung";
            if (preg_match('/(Samsung-|GT-|SM-|SCH-|SHV-)([.\-0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[2];
            }

        // Sony Ericsson
        } elseif (preg_match('/SonyEricsson/i', $user_agent)) {
            $link  = "http://en.wikipedia.org/wiki/SonyEricsson";
            $brand = "SonyEricsson";
            if (preg_match('/SonyEricsson([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // tcl
        } elseif (preg_match('/tcl/i', $user_agent)) {
            $link      = "http://www.tcl.com/";
            $brand     = "TCL";
            if (preg_match('/TCL[\ |\-]([0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // vivo
        } elseif (preg_match('/vivo/i', $user_agent)) {
            $link      = "http://www.vivo.com.cn/";
            $brand     = "vivo";
            if (preg_match('/VIVO ([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[1];
            }

        // Xperia
        } elseif (preg_match('/Xperia/i', $user_agent)) {
            $link      = "http://www.sonymobile.com/";
            $brand     = "Xperia";
            if (preg_match('/Xperia(-T)?( |\_|\-)?([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[3];
            }

        // ZTE
        } elseif (preg_match('/zte/i', $user_agent)) {
            $link      = "http://www.zte.com.cn/cn/";
            $brand     = "ZTE";
            if (preg_match('/ZTE(-T)?( |\_|\-)?([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model = $reg_match[3];
            }

        // Ubuntu Phone/Tablet
        } elseif (preg_match('/Ubuntu\;\ Mobile/i', $user_agent)) {
            $link      = "http://www.ubuntu.com/phone";
            $brand     = "Ubuntu Phone";
        } elseif (preg_match('/Ubuntu\;\ Tablet/i', $user_agent)) {
            $link      = "http://www.ubuntu.com/tablet";
            $brand     = "Ubuntu Tablet";

        // Google
        } elseif (preg_match('/Nexus/i', $user_agent)) {
            $link      = "https://www.google.com/nexus/";
            $brand     = "Google";
            $model     = "Nexus";
            if (preg_match('/Nexus ([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $model .= " " . $reg_match[1];
            }

        // Apple
        } elseif (preg_match('/iPad/i', $user_agent)) {
            $link      = "http://www.apple.com/itunes";
            $model     = "iPad";
            $brand     = 'Apple';
        } elseif (preg_match('/iPod/i', $user_agent)) {
            $link      = "http://www.apple.com/itunes";
            $model     = "iPod";
            $brand     = 'Apple';
        } elseif (preg_match('/iPhone/i', $user_agent)) {
            $link      = "http://www.apple.com/iphone";
            $model     = "iPhone";
            $brand     = 'Apple';

        // Some special UA..
        // is MSIE
        } elseif (preg_match('/MSIE.+?Windows.+?Trident/i', $user_agent) && ! preg_match('/Windows ?Phone/i', $user_agent)) {
            $link      = "";
            $brand     = "";
        }


        $cpu_brand = null;

        // 					1/intel 										     		  2/amd     3/ppc   	 4 			5
        if(preg_match( '/((?:x86_64)|(?:x86-64)|(?:Win64)|(?:WOW64)|(?:x64)|(?:ia64)) | (amd64) | (ppc64) | (sparc64) | (IRIX64)/ix', $user_agent, $info)) {
            if ( ! empty($info[1])) {
                $cpu_brand = 'Intel';

            } else if ( ! empty($info[2])) {
                $cpu_brand = 'AMD';

            } else if ( ! empty( $info[3])) {
                $cpu_brand = 'PPC';

            } else if ( ! empty( $info[4])) {
                $cpu_brand = 'sparc64';

            } else if ( ! empty( $info[5])) {
                $cpu_brand = 'IRIX64';
            }

        } else {
            if (strpos('amd', $user_agent) !== false) {
                $cpu_brand = 'AMD';

            } elseif (strpos('i386', $user_agent) !== false || strpos('x86', $user_agent) !== false || strpos('ia32', $user_agent) !== false) {
                $cpu_brand = 'Intel';
            }
        }


        return [
            'title'     => $brand . ($model == '' ? '' : ' ' . $model),
            'model'     => $model,
            'brand'     => $brand,
            'cpu_brand' => $cpu_brand,
            'link'      => $link,
        ];
    }


    /**
     * @param $user_agent
     * @return array
     */
    public static function getOs($user_agent = null) {

        if (is_null($user_agent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                return [
                    'title'   => null,
                    'name'    => null,
                    'version' => null,
                    'link'    => null,
                    'x64'     => null,
                ];
            }
        }

        // Check if is AMD64
        $x64 = false;
        if (preg_match('/x86_64|Win64; x64|WOW64|IRIX64/i', $user_agent)) {
            $x64 = true;
        }

        // Check Linux
        if (preg_match('/Windows|Win(NT|32|95|98|16)|ZuneWP7|WPDesktop/i', $user_agent)) {
            $result = self::analyzeWindows($user_agent);
        } elseif (preg_match('/Linux/i', $user_agent) && ! preg_match('/Android|ADR|Tizen/', $user_agent)) {
            $result = self::analyzeLinux($user_agent);
        } else {
            $result = self::analyzeOther($user_agent);
        }

        $result['x64']   = $x64;
        $result['title'] = $result['name'] . ($result['version'] == "" ? '' : ' ' . $result['version']) . ($x64 ? ' x64' : '');

        return $result;
    }


    /**
     * @param $user_agent
     * @return array
     */
    private static function analyzeWindows($user_agent) {

        $link    = "https://www.microsoft.com/windows/";
        $name    = 'Windows';
        $version = '';

        $return = [
            "version" => "",
        ];

        if (preg_match('/Windows Phone|WPDesktop|ZuneWP7|WP7/i', $user_agent)) {
            $link = "https://www.microsoft.com/windows/phones";
            $name .= ' Phone';

            if (preg_match('/Windows Phone (OS )?([0-9\.]+)/i', $user_agent, $reg_match)) {
                $version    = $reg_match[2];
                $intVersion = (int)$version;

                if ($intVersion == 10) {
                    $name    = "Windows";
                    $version = "10 Mobile";
                }
            }

        } elseif (preg_match('/Windows NT (\d+\.\d+)/i', $user_agent, $reg_match)) {
            if (isset(self::$_windows_version[$reg_match[1]])) {
                self::_returnWindows($return, $reg_match[1]);
            }

        } elseif (preg_match('/Windows 2000/i', $user_agent)) {
            self::_returnWindows($return, "5.0");
        } elseif (preg_match('/Windows XP/i', $user_agent)) {
            self::_returnWindows($return, "5.1");
        } elseif (preg_match('/Win(dows )?NT ?4.0|WinNT4.0/i', $user_agent)) {
            self::_returnWindows($return, "4.0");
        } elseif (preg_match('/Win(dows )?NT ?3.51|WinNT3.51/i', $user_agent)) {
            self::_returnWindows($return, "3.51");
        } elseif (preg_match('/Win(dows )?3.11|Win16/i', $user_agent)) {
            $version = "3.11";
        } elseif (preg_match('/Windows 3.1/i', $user_agent)) {
            $version = "3.1";
        } elseif (preg_match('/Win 9x 4.90|Windows ME/i', $user_agent)) {
            $version = "Me";
        } elseif (preg_match('/Win98/i', $user_agent)) {
            $version = "98 SE";
        } elseif (preg_match('/Windows (98|4\.10)/i', $user_agent)) {
            $version = "98";
        } elseif (preg_match('/Windows 95/i', $user_agent) || preg_match('/Win95/i', $user_agent)) {
            $version = "95";
        } elseif (preg_match('/Windows CE|Windows .+Mobile/i', $user_agent)) {
            $version = "CE";
        } elseif (preg_match('/WM5/i', $user_agent)) {
            $name    .= " Mobile";
            $version = "5";
        } elseif (preg_match('/WindowsMobile/i', $user_agent)) {
            $name .= " Mobile";
        }

        if ($return['version'] !== "") {
            $version = $return['version'];
        }

        return [
            'name'    => $name,
            'version' => $version,
            'link'    => $link,
        ];
    }


    /**
     * @param $user_agent
     * @return array
     */
    private static function analyzeLinux($user_agent) {

        $version = '';

        if (preg_match('/[^A-Za-z]Arch/i', $user_agent)) {
            $link = "http://www.archlinux.org/";
            $name = "Arch Linux";

        } elseif (preg_match('/CentOS/i', $user_agent)) {
            $link = "http://www.centos.org/";
            $name = "CentOS";
            if (preg_match('/.el([.0-9a-zA-Z]+).centos/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Chakra/i', $user_agent)) {
            $link = "http://www.chakra-linux.org/";
            $name = "Chakra Linux";
        } elseif (preg_match('/Crunchbang/i', $user_agent)) {
            $link = "http://www.crunchbanglinux.org/";
            $name = "Crunchbang";
        } elseif (preg_match('/Debian/i', $user_agent)) {
            $link = "http://www.debian.org/";
            $name = "Debian GNU/Linux";
        } elseif (preg_match('/Edubuntu/i', $user_agent)) {
            $link = "http://www.edubuntu.org/";
            $name = "Edubuntu";

            if (preg_match('/Edubuntu[\/|\ ]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }
            if (strlen($version) > 1) {
                $name .= $version;
            }

        } elseif (preg_match('/Fedora/i', $user_agent)) {
            $link = "http://www.fedoraproject.org/";
            $name = "Fedora";
            if (preg_match('/.fc([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Foresight\ Linux/i', $user_agent)) {
            $link = "http://www.foresightlinux.org/";
            $name = "Foresight Linux";
            if (preg_match('/Foresight\ Linux\/([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Gentoo/i', $user_agent)) {
            $link = "http://www.gentoo.org/";
            $name = "Gentoo";
        } elseif (preg_match('/Jolla/i', $user_agent)) {
            $link = "https://jolla.com/";
            $name = "Jolla";
        } elseif (preg_match('/Kanotix/i', $user_agent)) {
            $link = "http://www.kanotix.com/";
            $name = "Kanotix";
        } elseif (preg_match('/Knoppix/i', $user_agent)) {
            $link = "http://www.knoppix.net/";
            $name = "Knoppix";
        } elseif (preg_match('/Kubuntu/i', $user_agent)) {
            $link = "http://www.kubuntu.org/";
            $name = "Kubuntu";
            if (preg_match('/Kubuntu[\/|\ ]([.0-9]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/LindowsOS/i', $user_agent)) {
            $link = "http://en.wikipedia.org/wiki/Lsongs";
            $name = "LindowsOS";
        } elseif (preg_match('/Linspire/i', $user_agent)) {
            $link = "http://www.linspire.com/";
            $name = "Linspire";
        } elseif (preg_match('/Linux\ Mint/i', $user_agent)) {
            $link = "http://www.linuxmint.com/";
            $name = "Linux Mint";
            if (preg_match('/Linux\ Mint\/([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Lubuntu/i', $user_agent)) {
            $link = "http://www.lubuntu.net/";
            $name = "Lubuntu";
            if (preg_match('/Lubuntu[\/|\ ]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }
            if (strlen($version) > 1) {
                $name .= $version;
            }

        } elseif (preg_match('/Mageia/i', $user_agent)) {
            $link = "http://www.mageia.org/";
            $name = "Mageia";
        } elseif (preg_match('/Mandriva/i', $user_agent)) {
            $link = "http://www.mandriva.com/";
            $name = "Mandriva";
            if (preg_match('/mdv([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/moonOS/i', $user_agent)) {
            $link = "http://www.moonos.org/";
            $name = "moonOS";
            if (preg_match('/moonOS\/([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Nova/i', $user_agent)) {
            $link = "http://www.nova.cu";
            $name = "Nova";
            if (preg_match('/Nova[\/|\ ]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Oracle/i', $user_agent)) {
            $link = "http://www.oracle.com/us/technologies/linux/";
            $name = "Oracle";
            if (preg_match('/.el([._0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $name    .= " Enterprise Linux";
                $version = str_replace("_", ".", $reg_match[1]);
            } else {
                $name .= " Linux";
            }

        } elseif (preg_match('/Pardus/i', $user_agent)) {
            $link = "http://www.pardus.org.tr/en/";
            $name = "Pardus";
        } elseif (preg_match('/Red\ Hat/i', $user_agent) || preg_match('/RedHat/i', $user_agent)) {
            $link = "http://www.redhat.com/";
            $name = "Red Hat";
            if (preg_match('/.el([._0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $name    .= " Enterprise Linux";
                $version = str_replace("_", ".", $reg_match[1]);
            }

        } elseif (preg_match('/Slackware/i', $user_agent)) {
            $link = "http://www.slackware.com/";
            $name = "Slackware";
        } elseif (preg_match('/Suse/i', $user_agent)) {
            $link = "http://www.opensuse.org/";
            $name = "openSUSE";
        } elseif (preg_match('/Xubuntu/i', $user_agent)) {
            $link = "http://www.xubuntu.org/";
            $name = "Xubuntu";
            if (preg_match('/Xubuntu[\/|\ ]([.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } elseif (preg_match('/Zenwalk/i', $user_agent)) {
            $link = "http://www.zenwalk.org/";
            $name = "Zenwalk GNU Linux";

            // Pulled out of order to help ensure better detection for above platforms
        } elseif (preg_match('/Ubuntu/i', $user_agent)) {
            $link = "http://www.ubuntu.com/";
            $name = "Ubuntu";
            if (preg_match('/Ubuntu[\/|\ ]([.0-9]+[.0-9a-zA-Z]+)/i', $user_agent, $reg_match)) {
                $version = $reg_match[1];
            }

        } else {
            $link = "http://www.linux.org/";
            $name = "GNU/Linux";
        }

        return [
            'name'    => $name,
            'version' => $version,
            'link'    => $link,
        ];
    }


    /**
     * @param $user_agent
     * @return array
     */
    private static function analyzeOther($user_agent) {

        $link      = '';
        $name      = '';
        $version   = '';

        // Opera's Useragent does not contains 'Linux'
        if (preg_match('/Android|ADR /i', $user_agent)) {
            $link      = "http://www.android.com/";
            $name      = "Android";
            if (preg_match('/(Android|Adr)[\ |\/]([.0-9]+)/i', $user_agent, $regmatch)) {
                $version = $regmatch[2];
            }

        } elseif (preg_match('/CPU\ (iPhone )?OS\ ([._0-9a-zA-Z]+)/i', $user_agent, $regmatch)) {
            $link      = "http://www.apple.com/";
            $name      = "iOS";
            $version   = str_replace("_", ".", $regmatch[2]);
        } elseif (preg_match('/AmigaOS/i', $user_agent)) {
            $link = "http://en.wikipedia.org/wiki/AmigaOS";
            $name = "AmigaOS";
            if (preg_match('/AmigaOS\ ([.0-9a-zA-Z]+)/i', $user_agent, $regmatch)) {
                $version = $regmatch[1];
            }

        } elseif (preg_match('/BB10/i', $user_agent)) {
            $link      = "http://www.blackberry.com/";
            $name      = "BlackBerry OS";
            $version   = "10";
        } elseif (preg_match('/BeOS/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/BeOS";
            $name      = "BeOS";
        } elseif (preg_match('/\b(?!Mi)CrOS(?!oft)/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/Google_Chrome_OS";
            $name      = "Google Chrome OS";
        } elseif (preg_match('/DragonFly/i', $user_agent)) {
            $link      = "http://www.dragonflybsd.org/";
            $name      = "DragonFly BSD";
        } elseif (preg_match('/FreeBSD/i', $user_agent)) {
            $link      = "http://www.freebsd.org/";
            $name      = "FreeBSD";
        } elseif (preg_match('/Inferno/i', $user_agent)) {
            $link      = "http://www.vitanuova.com/inferno/";
            $name      = "Inferno";
        } elseif (preg_match('/IRIX/i', $user_agent)) {
            $link = "http://www.sgi.com/partners/?/technology/irix/";
            $name = "IRIX";
            if (preg_match('/IRIX(64)?\ ([.0-9a-zA-Z]+)/i', $user_agent, $regmatch)) {
                if ($regmatch[2]) {
                    $version = $regmatch[2];
                }
            }

        } elseif (preg_match('/Mac/i', $user_agent) || preg_match('/Darwin/i', $user_agent)) {
            $link = "http://www.apple.com/macosx/";
            if (preg_match('/(Mac OS ?X)/i', $user_agent, $regmatch)) {
                $name = substr($user_agent, strpos(strtolower($user_agent), strtolower($regmatch[1])));
                $name = substr($name, 0, strpos($name, ")"));
                if (strpos($name, ";")) {
                    $name = substr($name, 0, strpos($name, ";"));
                }
                $name         = str_replace("_", ".", $name);
                $name         = str_replace("OSX", "OS X", $name);
                $macOSVersion = strpos($name, "OS X") + 4;
                $version      = trim(substr($name, $macOSVersion));
                $name         = substr($name, 0, $macOSVersion);
                if (version_compare('10.12', $version) < 1) {
                    $name = 'macOS';
                }
            } elseif (preg_match('/Darwin/i', $user_agent)) {
                $name      = "Mac OS Darwin";
            } else {
                $name      = "Macintosh";
            }

        } elseif (preg_match('/Meego/i', $user_agent)) {
            $link      = "http://meego.com/";
            $name      = "Meego";
        } elseif (preg_match('/MorphOS/i', $user_agent)) {
            $link      = "http://www.morphos-team.net/";
            $name      = "MorphOS";
        } elseif (preg_match('/NetBSD/i', $user_agent)) {
            $link      = "http://www.netbsd.org/";
            $name      = "NetBSD";
        } elseif (preg_match('/OpenBSD/i', $user_agent)) {
            $link      = "http://www.openbsd.org/";
            $name      = "OpenBSD";
        } elseif (preg_match('/RISC OS/i', $user_agent)) {
            $link      = "https://www.riscosopen.org/";
            $name      = "RISC OS";
            if (preg_match('/RISC OS ([.0-9a-zA-Z]+)/i', $user_agent, $regmatch)) {
                $version = $regmatch[1];
            }

        } elseif (preg_match('/Solaris|SunOS/i', $user_agent)) {
            $link      = "http://www.sun.com/software/solaris/";
            $name      = "Solaris";
        } elseif (preg_match('/Symb(ian)?(OS)?/i', $user_agent)) {
            $link = "http://www.symbianos.org/";
            $name = "SymbianOS";
            if (preg_match('/Symb(ian)?(OS)?\/([.0-9a-zA-Z]+)/i', $user_agent, $regmatch)) {
                $version = $regmatch[3];
            }

        } elseif (preg_match('/Tizen/i', $user_agent)) {
            $link      = "https://www.tizen.org/";
            $name      = "Tizen";
        } elseif (preg_match('/Unix/i', $user_agent)) {
            $link      = "http://www.unix.org/";
            $name      = "Unix";
        } elseif (preg_match('/webOS/i', $user_agent)) {
            $link      = "http://en.wikipedia.org/wiki/WebOS";
            $name      = "Palm webOS";
        } elseif (preg_match('/J2ME\/MIDP/i', $user_agent)) {
            $link      = "http://java.sun.com/javame/";
            $name      = "J2ME/MIDP Device";
        }

        return [
            'name'    => $name,
            'version' => $version,
            'link'    => $link,
        ];
    }


    /**
     * @param $return
     * @param $index
     */
    private static function _returnWindows(&$return, $index) {

        $return['version'] = self::$_windows_version[$index][0];
    }


    /**
     * @param string $method
     * @param string $url
     * @param array  $params
     * @param array  $headers
     * @return array|string
     * @throws \Exception
     */
    private static function request($method, $url, $params = [], $headers = []) {

        $curl = curl_init();

        if ( ! empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST,       true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($params) ? http_build_query($params) : $params);
        } else {
            $url .= ! empty($params) ? '?' . http_build_query($params) : '';
        }

        curl_setopt($curl, CURLOPT_URL,            $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (is_resource($curl)) {
            $content_raw  = curl_exec($curl);
            $info         = curl_getinfo($curl);
            $content_type = '';

            if ( ! empty($info['content_type'])) {
                $content_type = $info['content_type'];
            }

            if (strpos($content_type, 'application/json') !== false) {
                $content = json_decode($content_raw, true);

                if (json_last_error()) {
                    return null;
                }

            } else {
                $content = $content_raw;
            }

            if (curl_errno($curl) > 0) {
                return null;
            }

        } else {
            return null;
        }

        curl_close($curl);

        return $content;
    }
}