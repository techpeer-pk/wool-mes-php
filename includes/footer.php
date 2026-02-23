</div>

<footer class="mt-5 py-3 bg-light text-center">
    <p class="mb-0 text-muted">Wool Production MES &copy; <?php echo date('Y'); ?> | By: <a style="text-decoration: none;" href="https:techpeer.online" target="_blank">techpeer.pk</a></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Bootstrap 5 JS -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script> -->

<!-- DataTables JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.6/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#batchTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: -1 }
        ],
        language: {
            search: "Search batches:",
            lengthMenu: "Show _MENU_ batches per page",
            info: "Showing _START_ to _END_ of _TOTAL_ batches",
            infoEmpty: "No batches available",
            infoFiltered: "(filtered from _MAX_ total batches)"
        }
    });
});
</script>


</body>
</html>