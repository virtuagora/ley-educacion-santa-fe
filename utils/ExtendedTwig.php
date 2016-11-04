<?php

use Slim\Slim;

class ExtendedTwig extends Twig_Extension {
    private $search  = array('[i]', '[/i]', '[s]', '[/s]', '[hr]',
                             '[/size]', '[/color]', '[/font]');
    private $replace = array('<em>', '</em>', '<del>', '</del>', '<hr>',
                             '</font>', '</span>', '</span>');

    private $searchRx  = array('~\[(/?)(b|u|sup|sub)\]~s',
                               '~\[(left|right|center|justify)\]~s',
                               '~\[/(?:left|right|center|justify)\](?:\R?)~s',
                               '~\[(/?)(ul|ol|li|table|tr|td)\](?:\R?)~s',
                               '~\[size=(.*?)\]~s', '~\[color=(.*?)\]~s', '~\[font=(.*?)\]~s',
                               '~\[url=(.*?)\](.*?)\[/url\]~s', '~\[url\](.*?)\[/url\]~s',
                               '~\[img=(.*?)\](.*?)\[/img\]~s', '~\[img\](.*?)\[/img\]~s',
                               '~\[youtube\](.*?)\[/youtube\]~s',
                               '~\R~s');
    private $replaceRx = array('<$1$2>',
                               '<p style="text-align:$1">',
                               '</p>',
                               '<$1$2>',
                               '<font size="$1">', '<span style="color:$1;">', '<span style="font-family:$1;">',
                               '<a href="$1">$2</a>', '<a href="$1">$1</a>',
                               '<img src="$2" alt="$1">', '<img src="$1" alt="">',
                               '<iframe width="560" height="315" src="//www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>',
                               '<br>');

    public function getFilters() {
        return [
            new Twig_SimpleFilter('bbCode', [$this, 'bbCodeFilter'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('clickableLinks', [$this, 'clickableLinksFilter'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions() {
        return [
            new Twig_SimpleFunction('avatarUrl', array($this, 'avatarUrlFunction')),
            new Twig_SimpleFunction('urlFor', array($this, 'urlFor')),
            new Twig_SimpleFunction('baseUrl', array($this, 'base')),
            new Twig_SimpleFunction('siteUrl', array($this, 'site')),
            new Twig_SimpleFunction('currentUrl', array($this, 'currentUrl')),
        ];
    }

    public function bbCodeFilter($str) {
        return preg_replace($this->searchRx, $this->replaceRx, str_replace($this->search, $this->replace, $str));
    }

    public function clickableLinksFilter($str) {
        $url = '/([--:\w?@%&+~#=]*\.[a-z]{2,4}\/{0,2})((?:[?&](?:\w+)=(?:\w+))+|[--:\w?@%&+~#=]+)?/';
        return preg_replace($url, '<a href="$0" target="_blank">$0</a>', $str);
    }

    public function avatarUrlFunction($type, $hash, $size) {
        switch ($type) {
            case 1:
                return 'https://www.gravatar.com/avatar/'.$hash.'?d=identicon&s='.$size;
            case 2:
                return 'http://graph.facebook.com/'.$hash.'/picture?width='.$size;
            default:
                return Slim::getInstance()->request()->getRootUri().'/img/usuario/'.$hash.'/'.$size.'.png';
        }
    }
    
    public function urlFor($name, $params = array(), $appName = 'default') {
        return Slim::getInstance($appName)->urlFor($name, $params);
    }

    public function site($url, $withUri = true, $appName = 'default') {
        return $this->base($withUri, $appName) . '/' . ltrim($url, '/');
    }

    public function base($withUri = true, $appName = 'default') {
        $req = Slim::getInstance($appName)->request();
        $uri = $req->headers->get('x-forwarded-host')?: $req->getUrl();
        if ($withUri) {
            $uri .= $req->getRootUri();
        }
        return $uri;
    }

    public function currentUrl($withQueryString = true, $appName = 'default') {
        $app = Slim::getInstance($appName);
        $req = $app->request();
        $uri = $req->getUrl() . $req->getPath();
        if ($withQueryString) {
            $env = $app->environment();

            if ($env['QUERY_STRING']) {
                $uri .= '?' . $env['QUERY_STRING'];
            }
        }
        return $uri;
    }

    public function getName() {
        return 'extended_twig';
    }
}
