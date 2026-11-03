let show = false;
let pwds = document.getElementsByClassName('pwx');

function togglePWD() {
    for (let i = 0; i < pwds.length; i++) {
        if (show) {
            pwds[i].type = "text";
        } else {
            pwds[i].type = "password";
        }
    }

    show = !show;
}

document.getElementById("tpw").addEventListener("click", togglePWD);

togglePWD();
