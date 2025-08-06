<?php
    $default = "path/to/defava.png"; // Set a Default Avatar
    $emailavatar = md5(strtolower(trim($email)));
    $gravurl = "";
    $imageProfile = '<img src="http://www.gravatar.com/avatar/'.$emailavatar.'?d='.$default.'&s=140&r=g&d=mm" class="img-circle" alt="">';
?>

<div class="container">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="text-center">
                <div class="col-md-8 col-lg-offset-2">
                    <div>
                        <div class="row">
                            <div class="col-md-3" >
                                <?php echo $imageProfile; ?>
                            </div>
                            <div class="col-md-7">
                                <h3><i class="fa fa-user-circle" aria-hidden="true"></i> <?php echo $first_name . " ". $last_name; ?></h3>
                                <h5><i class="fa fa-envelope-o" aria-hidden="true"></i> <?php echo $email; ?></h5>
                                <h5><i class="fa fa-sign-in" aria-hidden="true"></i> <?php echo $last_login; ?></h5>
                            </div>
                            <div class="col-md-2">
                                <div class="btn-group">
                                    <a class="btn dropdown-toggle btn-info" data-toggle="dropdown" href="#">
                                        <?=$this->lang->line('main_profile_action');?>
                                        <span class="icon-cog icon-white"></span><span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="<?php echo site_url();?>main/changeuser"><span class="icon-wrench"></span> <?=$this->lang->line('main_profile_action_edit');?></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>