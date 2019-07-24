<?php

namespace Frogbob\LaravelBlinky;

use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\DomCrawler\Crawler;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class BlinkyCompilerEngine extends CompilerEngine
{
    protected $files;
    protected $cachePath;

    public function __construct(CompilerInterface $compiler, Filesystem $files)
    {
        parent::__construct($compiler);
        $this->files = $files;
        if (! is_null($this->cachePath)) {
            $contents = $this->compileString($this->files->get($this->getPath()));

            $this->files->put($this->getCompiledPath($this->getPath()), $contents);
        }
    }

    public function get($path, array $data = [])
    {
        // dd($path)
        $results = parent::get($path, $data);
        
        $useInliner = config('view.laravel_blinky.use_inliner');

        if($useInliner || is_null($useInliner)) {

            $crawler = new Crawler();
            $crawler->addHtmlContent($results);
            
            $stylesheets = $crawler->filter('link[rel=stylesheet]');
            // collect hrefs
            $stylesheetsHrefs = collect($stylesheets->extract('href'));
            // remove links
            $stylesheets->each(function (Crawler $crawler) {;
                foreach ($crawler as $node) {
                    $node->parentNode->removeChild($node);
                }
            });
            $results = $crawler->html();
            // get the styles
            $files = $this->files;
            $styles = $stylesheetsHrefs->map(function ($stylesheet) use ($files) {
                $path = resource_path('assets/css/' . $stylesheet);
                return $files->get($path);
            })->implode("\n\n");
            $inliner = new CssToInlineStyles();

            return $inliner->convert($results, $styles);

        }

        return $results;
    }
    
    public function getFiles()
    {
        return $this->files;
    }
    
}
