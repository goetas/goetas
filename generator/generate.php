<?php
require __DIR__ . '/vendor/autoload.php';

$projects = [
    'jms' => [
        'jms/metadata',
        'jms/serializer',
        'jms/serializer-bundle',
        'jms/translation-bundle',
    ],
    'masterminds/html5',
    'doctrine/migrations',
    'friendsofsymfony/rest-bundle',
    'nelmio/api-doc-bundle',
    'goetas-webservices' => [
        'goetas-webservices/xsd2php',
        'goetas-webservices/soap-server',
        'goetas-webservices/soap-client',
        'goetas-webservices/soap-reader',
        'goetas-webservices/wsdl-reader',
        'goetas-webservices/xsd-reader'],
    'goetas' => [
        'goetas/twital',
        'goetas/multipart-upload-bundle',
    ],
    'willdurand' => [
        'willdurand/hateoas-bundle',
        'willdurand/hateoas',
    ],
    'hautelook' => [
        'hautelook/templated-uri-bundle',
        'hautelook/templated-uri-router',
    ]
];

$data = [];

$stats = function ($name) {

    $data = [];
    $path = sprintf('https://packagist.org/packages/%s', $name);

    $tmp = sys_get_temp_dir() . "/" . md5($path);

    if (!is_file($tmp) || $name == 'masterminds/html5-php') {
        file_put_contents($tmp, file_get_contents($path));
    }

    $html = new Masterminds\HTML5([
        'disable_html_ns' => true
    ]);
    $dom = $html->loadHTMLFile($tmp);
    $qp = qp($dom);
    $capture = [
        'Installs',
        'Stars',
    ];
    foreach ($capture as $name => $selector) {
        $txt = $qp->xpath("//a[. = '$selector']/../../text()")->text();
        $data[strtolower($selector)] = intval(str_replace(html_entity_decode('&#8201;'), '', trim($txt)));
    }

    $data['url'] = $qp->xpath("//a[@title='Canonical Repository URL']/@href")->text();
    $data['pkg'] = $path;
    $data['description'] = $qp->xpath("//p[@class='description']")->text();

    return $data;
};

$goOverProjects = function (array $projects) use (&$goOverProjects, $stats) {
    $ret = [];
    foreach ($projects as $name => $data) {
        if (is_array($data)) {
            $ret[$name] = $goOverProjects($data);
        } else {
            $ret[$data] = $stats($data);
        }
    }
    return $ret;
};

$desc = $goOverProjects($projects);

function indent($t)
{
    $l = explode("\n", $t);
    $l = array_map(function ($s) {
        return "    " . $s;
    }, $l);
    return implode("\n", $l);
}

$generate = function (array $desc) use (&$generate) {
    $ret = [];
    foreach ($desc as $name => $data) {
        if (is_array($data) && !isset($data['url'])) {
            $ret[] = '- ' . $name;
            $ret[] = indent($generate($data));
        } else {
            $d = $data['installs'];
            if ($d > 1000000) {
                $d = round($d / 1000000) . "M";
            } elseif ($d > 1000) {
                $d = round($d / 1000) . "K";
            }
            $stats = "[{$d}+ ⬇️]({$data['pkg']}), [{$data['stars']} ⭐]({$data['url']})";
            $ret[] = "- [$name]({$data['url']}), $stats";
            if ($data['description']) {
                $ret[] = "{$data['description']}\n";
            }
        }
    }
    return implode("\n", $ret);
};

$f = file_get_contents(__DIR__ . '/README.md');
$f = str_replace('[data]', $generate($desc), $f);
$f = file_put_contents(__DIR__ . '/../README.md', $f);



