(async () => {
let react = await import("https://esm.sh/react@18.2.0");
let reactDom = await import("https://esm.sh/react-dom@18.2.0");
let exampleComponent = () => {
return react.createElement("div", null, "Hello, React!");
};
reactDom.render(react.createElement(exampleComponent), document.querySelector("#root"));
})();
