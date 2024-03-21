document.addEventListener("click", function(e) {
    if(e.target.id == "submit_import") {
        const fileInput = document.getElementById('import-manager-file');
        if (fileInput.files && fileInput.files.length > 0) {
            alert("Processing please wait untill the data migrate.");
            e.target.setAttribute("disabled", "disabled");
            top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"))
            document.getElementById("importHandler").submit();
        } else {
            alert("Please select a csv file for process.");
        }
        
    }
});