document.getElementById('submit_import').onclick = function() {
    alert("Processing please wait until success massage.");
    this.disabled = true;
    document.getElementById("importHandler").submit();
}