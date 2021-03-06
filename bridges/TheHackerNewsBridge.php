<?php
class TheHackerNewsBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = $this->getName();
        $this->uri = $this->getURI();
        $this->description = 'Cyber Security, Hacking, Technology News.';
        $this->update = '2016-07-22';

    }

    public function collectData(array $param) {

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        function StripRecursiveHTMLSection($string, $tag_name, $tag_start) {
            $open_tag = '<'.$tag_name;
            $close_tag = '</'.$tag_name.'>';
            $close_tag_length = strlen($close_tag);
            if (strpos($tag_start, $open_tag) === 0) {
                while (strpos($string, $tag_start) !== false) {
                    $max_recursion = 100;
                    $section_to_remove = null;
                    $section_start = strpos($string, $tag_start);
                    $search_offset = $section_start;
                    do {
                        $max_recursion--;
                        $section_end = strpos($string, $close_tag, $search_offset);
                        $search_offset = $section_end + $close_tag_length;
                        $section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
                        $open_tag_count = substr_count($section_to_remove, $open_tag);
                        $close_tag_count = substr_count($section_to_remove, $close_tag);
                    } while ($open_tag_count > $close_tag_count && $max_recursion > 0);
                    $string = str_replace($section_to_remove, '', $string);
                }
            }
            return $string;
        }

        $html = $this->file_get_html($this->getURI()) or $this->returnError('Could not request TheHackerNews: '.$this->getURI(), 500);
        $limit = 0;

        foreach ($html->find('article') as $element) {
            if ($limit < 5) {

                $article_url = $element->find('a.entry-title', 0)->href;
                $article_author = trim($element->find('span.vcard', 0)->plaintext);
                $article_title = $element->find('a.entry-title', 0)->plaintext;
                $article_timestamp = strtotime($element->find('span.updated', 0)->plaintext);
                $article_thumbnail = $element->find('img', 0)->src;
                $article = $this->file_get_html($article_url) or $this->returnError('Could not request TheHackerNews: '.$article_url, 500);

                $contents = $article->find('div.articlebodyonly', 0)->innertext;
                $contents = StripRecursiveHTMLSection($contents, 'div', '<div class=\'clear\'');
                $contents = StripWithDelimiters($contents, '<script', '</script>');

                $item = new \Item();
                $item->uri = $article_url;
                $item->title = $article_title;
                $item->author = $article_author;
                $item->thumbnailUri = $article_thumbnail;
                $item->timestamp = $article_timestamp;
                $item->content = trim($contents);
                $this->items[] = $item;
                $limit++;
            }
        }

    }

    public function getName() {
        return 'The Hacker News Bridge';
    }

    public function getURI() {
        return 'https://thehackernews.com/';
    }

    public function getCacheDuration() {
        return 3600; //1 hour
    }
}