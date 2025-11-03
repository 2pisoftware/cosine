<?php

declare(strict_types=1);

class CmfiveScriptComponent extends CmfiveComponent
{
    public string $tag = 'script';
    public string $type = 'text/javascript';
    public bool $has_closing_tag = true;
    public string $src = '';

    public function __construct(string $path, array $props = [])
    {
        $this->src = $path;

        if (!empty($props)) {
            foreach ($props as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
