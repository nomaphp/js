let name = "John Doe";
let parts = name.split(" ");
let newParts = [];
for (const part of parts) {
newParts.push(part.toUpperCase());
}
console.log(newParts.join(" "));
