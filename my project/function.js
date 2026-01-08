


function showdata(){
let name=document.getElementById("name").value;

let qual=document.getElementById("qual").value;
let age=document.getElementById("num").value;
let location=document.getElementById("location").value;
let contact=document.getElementById("contact").value;

      document.getElementById("one").innerHTML =
    "Name: " + name + "<br>" +
    "Qualification: " + qual + "<br>" +
    "Age: " + age + "<br>" +
    "Location: " + location + "<br>" +
    "Contact: " + contact;

}
