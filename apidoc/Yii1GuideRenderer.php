<?php

namespace app\apidoc;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Json;

class Yii1GuideRenderer extends Component
{
    public $basePath;
    public $targetPath;

    public $section = 'documentation';

    public $tocFile = 'toc';
    /**
     * @var string the type of the tutorial. This refers to the directory name containing the tutorial files.
     */
    public $tutorialType;
    /**
     * @var string title of the tutorial
     */
    public $tutorialTitle;
    /**
     * @var string the default tutorial file name
     */
    public $defaultSection;
    /**
     * @var string
     */
    public $language;
    /**
     * @var string
     */
    public $version;

    public $sectionTitle;
    public $headings = array();

    private $_toc;      // chapter title, section title => path
    private $_sections; // path => chatper title, section title


    public function init()
    {
        parent::init();
        if (empty($this->basePath) || empty($this->targetPath)) {
            throw new Exception('basePath and targetPath are required.');
        }
    }

    public function renderGuide($version, $language)
    {
        $this->tutorialType = 'guide';
        $this->tutorialTitle = 'The Definitive Guide to Yii';
        $this->defaultSection = 'index';
        $this->language = $language;
        $this->version = $version;

        $this->renderTutorial();
    }

    public function renderBlog($version, $language)
    {
        $this->tutorialType = 'blog';
        $this->tutorialTitle = 'Building a Blog System Using Yii';
        $this->defaultSection = 'start.overview';
        $this->language = $language;
        $this->version = $version;

        $this->renderTutorial();
    }

    protected function renderTutorial()
    {
        foreach($this->getSections() as $section => $title) {
            $this->headings = [];

            $file = $this->basePath . DIRECTORY_SEPARATOR . $section . '.txt';
            $file = FileHelper::localize($file, $this->language, 'en');
            if (!is_file($file))
                continue;

            $content = $this->getContent($file);

            file_put_contents($this->targetPath . '/' . $section . '.html', $content); // TODO TOC

            // extract toc as json
            $headings = [
                'h1' => '',
                'id' => '',
                'sections' => $this->headings,
            ];
            if (preg_match('/<h1>(.+?)(\s*<span id="(.+?)">.*?)?<\/h1>/i', $content, $matches)) {
                $headings['h1'] = $matches[1];
                $headings['id'] = isset($matches[3]) ? $matches[3] : '';
            } elseif (preg_match('/<h1 id="(.+?)">(.+?)<\/h1>/i', $content, $matches)) {
                $headings['h1'] = $matches[2];
                $headings['id'] = $matches[1];
            }
            file_put_contents($this->targetPath . '/' . $section . '.json', Json::encode($headings));
        }
    }

    protected function getContent($file)
    {
        return $this->filterContent(file_get_contents($file));
    }

    public function getToc()
    {
        if ($this->_toc === null) {
            $file = $this->basePath . DIRECTORY_SEPARATOR . $this->tocFile . '.txt';
            $file = FileHelper::localize($file, $this->language, 'en');
            $lines = file($file);
            $chapter = '';
            foreach ($lines as $line) {
                // trim unicode BOM from line
                $line = trim(ltrim($line, "\xEF\xBB\xBF"));
                if ($line === '') {
                    continue;
                }
                if ($line[0] === '*') {
                    $chapter = trim($line, '* ');
                } elseif ($line[0] === '-' && preg_match('/\[(.*?)\]\((.*?)\)/', $line, $matches)) {
                    $this->_toc[$chapter][$matches[1]] = $matches[2];
                    $this->_sections[$matches[2]] = array($chapter, $matches[1]);
                }
            }
        }
        return $this->_toc;
    }

    public function getSections()
    {
        $this->getToc();
        return $this->_sections;
    }

    protected function filterContent($content)
    {
        // strip SVN ID
        $content = preg_replace('~[<«»]+div class="revision"[>«»].+?[<«»]/div[>«»]+~', '', $content);

        // transform markdown
        $markdown = new Yii1MarkdownParser();
        $content = $markdown->transform($content);

        // adjust URLs
        $guideBaseUrl = $this->tutorialType == 'blog' ? Yii::$app->params['blogtut.baseUrl'] :  Yii::$app->params['guide.baseUrl'];
        $apiBaseUrl = Yii::$app->params['api.baseUrl'];
        $content = preg_replace("/href=\"\/doc\/{$this->tutorialType}/",
            "href=\"$guideBaseUrl/{$this->version}/{$this->language}", $content);
        $content = preg_replace("/href=\"\/doc\/api/",
            "href=\"$apiBaseUrl/{$this->version}", $content);
//        $content = str_replace('href="/doc/', "href=\"{$bu}doc/", $content);
        $content = preg_replace('/<p>\s*<img(.*?)src="(.*?)"\s+alt="(.*?)"\s*\/>\s*<\/p>/',
            '<div class="image"><p>\3</p><img\1class="img-responsive" src="' . "$guideBaseUrl/{$this->version}/{$this->language}/images/\\2" .'" alt="\3" /></div>', $content);
        $content = preg_replace_callback('!<h(1|2|3) id="([^"]+)"\s*>(.+?)</h\d>!', array($this, 'headings'), $content);

        // generate TOC
        if (count($this->headings) > 1) {
            $toc = array();
            foreach ($this->headings as $heading)
                $toc[] = '<li>' . Html::a($heading['title'], '#' . $heading['id']) . '</li>';
            $toc = '<div class="toc hidden-lg"><ol>' . implode("\n", $toc) . "</ol></div>\n";
            if (strpos($content, '</h1>') !== false)
                $content = str_replace('</h1>', "</h1>\n" . $toc, $content);
            else
                $content = $toc . $content;
        }

        return $content;
    }

    protected function headings($match)
    {
        $level = intval($match[1]);
        $id = $match[2];
        $title = $match[3];

        if ($level == 2) {
            $this->headings[] = array('title' => $title, 'id' => $id);
            $section = count($this->headings);
            $anchor = sprintf('<a class="hashlink" href="#%s">¶</a>', $id);
            return sprintf('<h%d id="%s">%s. %s %s</h%d>', $level, $id, $section, $title, $anchor, $level);
        } elseif ($level > 2) {
            if (end($this->headings)) {
                $this->headings[key($this->headings)]['sub'][] = [
                    'title' => trim($title),
                    'id' => $id,
                ];
            }
        }
        return $match[0];
    }
}
