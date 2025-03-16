(async () => {
let react = await import("https://esm.sh/react@18.2.0");
let reactDom = await import("https://esm.sh/react-dom@18.2.0");
let exampleComponent = () => {
let [state, setState] = react.useState(0);
react.useEffect(() => {
console.log("This runs on mount.");
}, []);
return react.createElement("button", {"onClick": () => setState(state + 1)}, `Clicked  ${state}  times.`);
};
reactDom.render(react.createElement(exampleComponent), document.querySelector("#root"));
})();