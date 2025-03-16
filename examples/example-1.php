<?php

use Noma\Js\Jsi;

(new Jsi)->alert(123);

$item = (new Jsi\Document)->querySelector(".selector");
$items = (new Jsi\Document)->querySelectorAll(".selector");

(new Jsi\Document)->querySelector("button")->addEventListener("click", function(Jsi\Event $e) {
    echo "test";
});
