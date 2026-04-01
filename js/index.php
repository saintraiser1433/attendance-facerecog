<?php
include '../connection.php';
if (!isset($_SESSION['admin_id'])) {
  header("Location:../index.php");
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include '../components/head.php' ?>
<script src="../lib/js/es6-shim.js"></script>
<script src="../lib/js/websdk.client.bundle.min.js"></script>
<script src="../lib/js/fingerprint.sdk.min.js"></script>
<script src="../lib/js/custom.js"></script>



<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
    <?php include '../components/sidebar.php' ?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <?php include '../components/topbar.php' ?>
        <!-- Topbar -->
        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">

          <!-- Row -->
          <div class="row">
            <!-- Datatables -->
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Manage Student</h6>
                  <a href="#" class="btn btn-primary btn-icon-split btn-sm addStudent">
                    <span class="icon text-white-50">
                      <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Add Student</span>
                  </a>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Year</th>
                        <th>Course</th>
                        <th>Middle Finger</th>
                        <th>Index Finger</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sql = "SELECT
                        stud.student_id,
                        CONCAT(
                            stud.last_name,
                            ', ',
                            stud.first_name,
                            ' ',
                            LEFT(stud.middle_name, 1)
                        ) AS fname,
                        stud.year_level,
                        crs.course_name,
                        stud.block,
                        stud.middle_finger,
                        stud.index_finger
                    FROM
                        student stud
                    LEFT JOIN department dept ON
                        stud.department_id = dept.department_id
                    LEFT JOIN course crs ON
                        stud.course_id = crs.course_id
                    ORDER BY
                        stud.date_created ASC";
                      $rs = $conn->query($sql);
                      foreach ($rs as $row) {
                      ?>
                        <tr>
                          <td><?php echo $row['student_id'] ?></td>
                          <td class="text-capitalize"><?php echo $row['fname'] ?></td>
                          <td class="text-capitalize"><?php echo $row['year_level'] ?></td>
                          <td class="text-capitalize"><?php echo $row['course_name'] ?></td>
                          <td>
                            <?php
                            if ($row['middle_finger'] == '' || $row['middle_finger'] == null) {
                              echo ' <span class="badge badge-danger p-2"><i class="fas fa-times"></i></span> ';
                            } else {
                              echo ' <span class="badge badge-success p-2"><i class="fas fa-check"></i></span> ';
                            }
                            ?></td>
                          <td>
                            <?php
                            if ($row['index_finger'] == '' || $row['index_finger'] == null) {
                              echo ' <span class="badge badge-danger p-2"><i class="fas fa-times"></i></span> ';
                            } else {
                              echo ' <span class="badge badge-success p-2"><i class="fas fa-check"></i></span> ';
                            }
                            ?></td>
                          <td>
                            <a href="view_student_sched.php?st=<?php echo $row['student_id'] ?>&nm=<?php echo $row['fname'] ?>" class="badge badge-success p-2 assign" title="View Schedule"><i class="fas fa-eye"></i></a> |
                            <a href="#" class="badge badge-warning p-2 edit" title="Edit"><i class="fas fa-edit"></i></a> |
                            <a href="#" class="badge badge-danger p-2 delete" title="Delete"><i class="fas fa-trash"></i></a>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!--Row-->

        </div>
        <!---Container Fluid-->
      </div>

      <!-- Footer -->
      <?php include '../components/footer.php' ?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Modal here -->
  <?php include '../components/modal.php' ?>

  <!-- Script here -->
  <?php include '../components/script.php' ?>

  <!-- Page level custom scripts -->
  <script>
    function readURL(input) {
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          $('#ImgID').attr('src', e.target.result);

        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    $(document).ready(function() {
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover


      $('.addStudent').on('click', function() {
        $('#ImgID').attr('src','../lib/img/avatar.jpg')
        $('#isUpdate').val(0)
        $('#userID').val('');
        $('#reglname').val('');
        $('#regmname').val('');
        $('#regfname').val('');
        $('#regyearlevel').val('');
        $('#regdepartment').val('');
        $('#regcourse').val('');
        $('#regblock').val('');
        $('#exampleModalLabel').html('Register Student')
        $('#exampleModal').modal('show');
      });

      $('.edit').on('click', function() {
        var currentRow = $(this).closest("tr");
        $tr = $(this).closest('tr');
        var data = $tr.children("td").map(function() {
          return $(this).text();
        }).get();
        $.ajax({
          method: "GET",
          url: "../ajax/selectStudent.php",
          data: {
            studentID: data[0],
          },
          success: function(html) {
            $('#exampleModalLabel').html('Update Student')
            $('#isUpdate').val(1)
            $('#userID').val(html.student_id);
            $('#reglname').val(html.last_name);
            $('#regmname').val(html.middle_name);
            $('#regfname').val(html.first_name);
            $('#regyearlevel').val(html.year_level);
            $('#regdepartment').val(html.department_id);
            $('#regcourse').val(html.course_id);
            $('#regblock').val(html.block);
            $('#ImgID').attr('src',`../lib/studentimage/${html.img_path}`)
            // $('#updateSubject').show();
            $('#exampleModal').modal('show');
          }
        });
      });



      $('#createButton').on('click', function() {
        var userId = $('#userID').val();
        var regfname = $('#regfname').val();
        var reglname = $('#reglname').val();
        var regmname = $('#regmname').val();
        var regyearlevel = $('#regyearlevel').val();
        var regdepartment = $('#regdepartment').val();
        var regcourse = $('#regcourse').val();
        var regblock = $('#regblock').val();
        if (userId == '' || regfname == '' || regmname == '' || reglname == '' || regyearlevel == '' || regdepartment == '' || regcourse == '' || regblock == '') {
          alert('Please enter all value before you enrolling the fingerprint.');
          return;
        }
        $('#modal-fingerprint').modal('show');
      });


      $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        var currentRow = $(this).closest("tr");
        var col1 = currentRow.find("td:eq(0)").text();
        swal({
            title: "Are you sure?",
            text: "You want to delete this course?",
            icon: "warning",
            buttons: true,
            dangerMode: true,
          })
          .then((isConfirm) => {
            if (isConfirm) {
              $.ajax({
                method: "POST",
                url: "../ajax/delete.php",
                data: {
                  id: col1,
                  action: 'STUDENT'
                },
                success: function(html) {
                  swal("Success", {
                    icon: "success",
                  }).then((value) => {
                    location.reload();
                  });
                }
              });
            }
          });
        // ID From dataTable with Hover
      });
    });

    $('#upload').on('click', function() {
      var b = document.getElementById('customFile');
      b.click();
    });
  </script>

</body>

</html>