import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
document.addEventListener('DOMContentLoaded', () => {
    $('#loglist-table').DataTable({
        ordering: false,
        searching: false,
        language: {
            lengthMenu: "Display _MENU_ Logs",
            emptyTable: "No Logs Available",
            zeroRecords: "No matching Logs found"
        }
    });

    $('.ns-ext-table-block-wrap .dataTables_length select, .ns-ext-table-wrap .dataTables_filter input')
        .addClass('form-control');

    document.addEventListener("click", function (e) {
       
        if (e.target.id === "submit_import") {
            const fileInput = document.getElementById('import-manager-file');
            if (fileInput.files && fileInput.files.length > 0) {
                alert("Processing, please wait until the data migrates.");
                e.target.setAttribute("disabled", "disabled");
                document.getElementById("importHandler").submit();
            } else {
                alert("Please select a CSV file to process.");
            }
        }
    });
});