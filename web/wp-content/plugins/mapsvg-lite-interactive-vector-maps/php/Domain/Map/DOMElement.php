<?php
namespace MapSVG;

class DOMElement {
  public $tag;
  public $attributes;
  public $content;

  public function __construct($tag, $attributes = [], $content = '') {
      $this->tag = $tag;
      $this->attributes = $attributes;
      $this->content = $content;
  }

  public function render() {
      $attributesString = '';
      foreach ($this->attributes as $key => $value) {
          $attributesString .= " $key=\"$value\"";
      }
      return "<{$this->tag}{$attributesString}>{$this->content}</{$this->tag}>";
  }
}

