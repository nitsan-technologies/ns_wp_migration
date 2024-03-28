$(function() {
    $('#loglist-table').DataTable({
        "ordering": false,
        "searching": false,
        "language": {
            "lengthMenu": "Display _MENU_ Logs",
            "emptyTable": "No Logs Available",
            "zeroRecords": "No matching Logs found"
        },
    });
});

document.addEventListener("click", function(e) {
    if(e.target.id == "submit_import") {
        const fileInput = document.getElementById('import-manager-file');
        if (fileInput.files && fileInput.files.length > 0) {
            alert("Processing please wait untill the data migrate.");
            e.target.setAttribute("disabled", "disabled");
            document.getElementById("importHandler").submit();
        } else {
            alert("Please select a csv file for process.");
        }     
    }
});