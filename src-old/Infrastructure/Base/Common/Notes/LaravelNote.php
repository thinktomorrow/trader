<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Integration\Base\Common\Notes;

class LaravelNote
{
    /** @var string */
    private $message;

    /** @var array */
    private $tags = [];

    /** @var string */
    private $htmlTag;

    private $toast = false;

    private $toastFlair;

    /** @var string */
    private $class;

    private function __construct(string $message)
    {
        $this->message = $message;
        $this->element('span','');
    }

    public static function fromMessage(string $transkey, array $replace = [])
    {
        $translations = array_fill_keys(config('translatable.locales'), null);

        foreach($translations as $locale => $value){
            $translations[$locale] = trans($transkey, $replace, $locale);
        }

        return new static($translations);
    }

    public static function fromTranslations(array $translations)
    {
        return new static($translations);
    }

    public function tag(...$tags)
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }

    public function element(string $htmlTag, string $class = '')
    {
        $this->htmlTag = $htmlTag;
        $this->class = $class;

        return $this;
    }

    public function subtle()
    {
        return $this->element('span','text-xs text-grey-light mt-1');
    }

    public function secondary()
    {
        return $this->element('span','tag tag-secondary');
    }

    public function red()
    {
        return $this->element('span','tag tag-red mt-1');
    }

    public function toast($toastFlair = 'info')
    {
        $this->toast = true;
        $this->toastFlair = $toastFlair;

        return $this;
    }

    public function render(array $tags = [], string $locale = null): string
    {
        // Don't render this note if none of the requested tags aren't present
        if(count($tags) > 0 && count(array_intersect($this->tags, $tags)) < 1) return '';

        if(!$locale) $locale = app()->getLocale();

        if(! isset($this->translations[$locale])) return '';

        // Display the note as a toast.
        if($this->toast){
            $toast = '<div class="note-inner">'. $this->translations[$locale] .'</div>';
            return '<div data-closeNote class="note note--'.$this->toastFlair.' --toast pin-r">'.$toast.'<a class="close-note" aria-label="Close"><svg width="18" height="18" class="fill-current"><use xlink:href="#icon-remove-circle"></use></svg></a></div>';
        }

        return '<'.$this->htmlTag.' class="'.$this->class.'">'.$this->translations[$locale].'</'.$this->htmlTag.'>';
    }
}
