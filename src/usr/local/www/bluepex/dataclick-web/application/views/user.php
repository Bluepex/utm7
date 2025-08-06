<div class="container">
  <div class="well title">
		<h3 class="text-center"><?=$this->lang->line('utm_template_menu_users_action');?></h3>
	</div>
  <div class="panel panel-default">
    <div class="panel-body">
      <div id="dashboard-page">
        <table class="table table-hover table-bordered table-striped">
          <tr>
              <th><?=$this->lang->line("utm_users_report_name")?></th>
              <th><?=$this->lang->line("utm_users_report_username")?></th>
              <th><?=$this->lang->line("utm_users_report_lastlogin")?></th>
              <th><?=$this->lang->line("utm_users_report_levelname")?></th>
              <th><?=$this->lang->line("utm_users_report_status")?></th>
              <th><?=$this->lang->line("utm_users_report_edit")?></th>
          </tr>
                <?php
                    foreach($groups as $row)
                    { 
                    if($row->role == 1){
                        $rolename = "Admin";
                    }elseif($row->role == 2){
                        $rolename = "Author";
                    }elseif($row->role == 3){
                        $rolename = "Editor";
                    }elseif($row->role == 4){
                        $rolename = "Subscriber";
                    }
                    
                    echo '<tr>';
                    echo '<td class="td-middle">'.$row->first_name.'</td>';
                    echo '<td class="td-middle">'.$row->email.'</td>';
                    echo '<td class="td-middle">'.$row->last_login.'</td>';
                    echo '<td class="td-middle">'.$rolename.'</td>';
                    echo '<td class="td-middle">'.$row->status.'</td>';
                    echo '<td class="td-middle"><a href="'.site_url().'main/changelevel" class="padding-10px"><button type="button" class="btn btn-primary">' . $this->lang->line("utm_users_report_role"). '</button></a>';
                    echo '<a href="'.site_url().'main/deleteuser/'.$row->id.'" class="padding-10px"><button type="button" class="btn btn-danger">' . $this->lang->line("utm_users_report_delete") . '</button></a></td>';
                    echo '</tr>';
                    }
                ?>
        </table>
      </div>   
    </div>   
  </div>
</div>