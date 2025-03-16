<?php

use Noma\Js\Jsi;
use Noma\Js\Jsi\Stubs\React;
use Noma\Js\Jsi\Stubs\ReactDom;

(new Jsi)->async(function () {
    $react = (new Jsi)->import(from: "https://esm.sh/react@18.2.0", as: React::class);
    $reactDom = (new Jsi)->import(from: "https://esm.sh/react-dom@18.2.0", as: ReactDom::class);

    $exampleComponent = function () use ($react) {
        return $react::createElement("div", null, "Hello, React!");
    };

    $reactDom::render($react::createElement($exampleComponent), (new Jsi\Document)->querySelector("#root"));
});
