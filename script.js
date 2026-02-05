function updateClock() { 
    const now = new Date(); 
    const hours = String(now.getHours()).padStart(2, '0'); 
    const minutes = String(now.getMinutes()).padStart(2, '0'); 
    const seconds = String(now.getSeconds()).padStart(2, '0'); 
     
    const timeString = `${hours}:${minutes}:${seconds}`; 
    document.getElementById('clock').textContent = timeString; 
} 
 
// Update the clock immediately and set an interval to update it every second 
updateClock(); 
setInterval(updateClock, 1000); 

// console.log(dateToEpoch(new Date()));
// console.log(dateToEpoch(new Date(1408704590485)));

// console.log(dateToEpoch(new Date()));
// console.log(dateToEpoch(new Date(1408704590485)));


function dateToEpoch(thedate) {
    var time = thedate.getTime();
    return time - (time % 86400000);
}

dateToEpoch(new Date(1408704590485));
dateToEpoch(new Date());

function dateToEpoch2(thedate) {
    return thedate.setHours(0,0,0,0).getTime();
}

function uploadFiles() {
    let projectFile = document.getElementById("image-file").files[0];
    let formData = new FormData();
        
    formData.append("project", projectFile);
    fetch('/upload/image', {method: "POST", body: formData});
}

function submitForm() {

}