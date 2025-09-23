<?php

declare(strict_types=1);

class VueComponent extends CmfiveComponent
{
    public function __construct(
        public string $name,
        public string $js_path,
        public string $css_path = ''
    ) {
        $this->name = $name;
        $this->js_path = $js_path;
        $this->css_path = $css_path;
    }

    public function include()
    {
        if ($this->is_included) {
            return '';
        }

        $this->is_included = true;
        return (!empty($this->css_path) ? '<link rel="stylesheet" href="' . $this->css_path . '" />' : '') .
            '<script src="' . $this->js_path . '"></script>';
    }

    public function display($binding_data = [])
    {
        $buffer = '<' . $this->name . ' ';

        if (!empty($binding_data)) {
            foreach ($binding_data as $field => $value) {
                $buffer .= $field . '=\'' . $value . '\' ';
            }
        }

        return $buffer . '></' . $this->name . '>';
    }
}
