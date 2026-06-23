document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("joinCreate").addEventListener('change', (e) => {
        document.getElementById("roomIdLabel").classList.toggle("show");
    });
});