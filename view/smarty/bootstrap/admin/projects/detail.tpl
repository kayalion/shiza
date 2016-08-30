{extends file="base/index"}

{block name="head_title" prepend}{$project->getCode()} | {translate key="title.projects"} | {/block}

{block name="scripts" append}
    {script src="bootstrap/js/jquery-stickytabs.js"}
{/block}

{block name="scripts_inline" append}
<script type="text/javascript">
    function updateQueueStatus(url, sleepTime) {
        $.get(url, function(body) {
            if (body.data.attributes.status == 'error') {
                window.location.reload();
            } else {
                setTimeout(function() { updateQueueStatus(url, sleepTime)}, sleepTime * 1000);
            }
        }).fail(function(data) {
            if (data.status == 404) {
                window.location.reload();
            }
        });
    }
    $(function() {
        $('.nav-tabs').stickyTabs();

        $(".varnish-vcl").toggle();
        $(".varnish-vcl-toggle").on('click', function(e) {
            e.preventDefault();

            $(".varnish-vcl").toggle();
        });

        var url = "{url id="api.queue-jobs.detail" parameters=["id" => "%id%"]}";

        $("[data-queue]").each(function() {
            var $this = $(this);

            var jobUrl = url;
            jobUrl = jobUrl.replace('%25id%25', $this.data('queue-id'));

            updateQueueStatus(jobUrl, 3);
        });
    });
</script>
{/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.projects"}
            <small>
                {$project->getName()}
                {if $project->isDeleted()}
                    <span class="label label-warning">{translate key="label.status.deleting"}</span>
                {/if}
            </small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {function queueLabel jobStatus=null container=null isDeleted=false}
    {if $jobStatus}
        {$attributes = null}
        {if $jobStatus->getStatus() == 'error'}
            {$type = "danger"}
            {$label = "label.status.error"}
        {else}
            {$type = "warning"}
            {if $isDeleted}
                {$label = "label.status.deleting"}
            {else}
                {$label = "label.status.working"}
            {/if}

            {$attributes = " data-queue=\"`$jobStatus->getQueue()`\" data-queue-id=\"`$jobStatus->getId()`\""}
        {/if}

        <small>
            <span class="label label-{$type}"{$attributes}">
                {translate key=$label}
            </span>
        </small>
    {/if}
    {/function}

    <div class="btn-group">
        <a class="btn btn-default" href="{url id="projects.edit" parameters=["project" => $project->getCode()]}?referer={$app.url.request|urlencode}">
            <span class="glyphicon glyphicon-pencil"></span>
            {translate key="button.project.edit"}
        </a>
        <a class="btn btn-default" href="{url id="projects.delete" parameters=["project" => $project->getCode()]}?referer={$app.url.request|urlencode}">
            <span class="glyphicon glyphicon-trash"></span>
            {translate key="button.project.delete"}
        </a>
    </div>
    <a class="btn" href="{url id="projects"}">
        {translate key="button.back.overview"}
    </a>

    <p>&nbsp;</p>

    <div>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#notes" aria-controls="notes" role="tab" data-toggle="tab">
                    {translate key="title.notes"}
                </a>
            </li>
            <li role="presentation">
                <a href="#credentials" aria-controls="credentials" role="tab" data-toggle="tab">
                    {translate key="title.credentials"}
                    {call queueLabel jobStatus=$project->getCredentialsQueueJobStatus() container="#credentials"}
                </a>
            </li>
            <li role="presentation">
                <a href="#environments" aria-controls="environments" role="tab" data-toggle="tab">
                    {translate key="title.environments"}

                    {$jobStatus = null}
                    {foreach $project->getEnvironments() as $environment}
                        {if !$environment->getQueueJobStatus()}
                            {continue}
                        {/if}

                        {$jobStatus = $environment->getQueueJobStatus()}

                        {break}
                    {/foreach}

                    {call queueLabel jobStatus=$jobStatus container="#environments"}
                </a>
            </li>
            <li role="presentation">
                <a href="#databases" aria-controls="databases" role="tab" data-toggle="tab">
                    {translate key="title.databases"}

                    {$jobStatus = null}
                    {foreach $project->getDatabases() as $database}
                        {if !$database->getQueueJobStatus()}
                            {continue}
                        {/if}

                        {$jobStatus = $database->getQueueJobStatus()}

                        {break}
                    {/foreach}

                    {call queueLabel jobStatus=$jobStatus}
                </a>
            </li>
            <li role="presentation">
                <a href="#cron" aria-controls="cron" role="tab" data-toggle="tab">
                    {translate key="title.cron"}
                    {call queueLabel jobStatus=$project->getCronQueueJobStatus() container="#cron"}
                </a>
            </li>
            <li role="presentation">
                <a href="#varnish" aria-controls="varnish" role="tab" data-toggle="tab">
                    {translate key="title.varnish"}
                    {call queueLabel jobStatus=$project->getVarnishQueueJobStatus() container="#varnish"}
                </a>
            </li>
            <li role="presentation"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">{translate key="title.messages"}</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="notes">
                {$referer = "`$app.url.request`#notes"|urlencode}
                {$referer = "?referer=`$referer`"}

                {if $project->getNotes()}
                    {$project->getNotes()|decorate:"markdown"}
                    <p>&nbsp;</p>
                    <p>
                        <a href="{url id="projects.notes" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                            <span class="glyphicon glyphicon-pencil"></span>
                            {translate key="button.notes.edit"}
                        </a>
                    </p>
                {else}
                    <p>{translate key="label.notes.none"}</p>
                    <p>
                        <a href="{url id="projects.notes" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                            <span class="glyphicon glyphicon-plus"></span>
                            {translate key="button.notes.add"}
                        </a>
                    </p>
                {/if}
            </div>

            <div role="tabpanel" class="tab-pane" id="credentials">
                {$referer = "`$app.url.request`#credentials"|urlencode}
                {$referer = "?referer=`$referer`"}

                <dl>
                    <dt>{translate key="label.username"}</dt>
                    <dd>{$project->getCode()|lower}</dd>
                    {if $password}
                    <dt>{translate key="label.password"}</dt>
                    <dd>{$password}</dd>
                    {/if}
                </dl>

                {$sshKeys = $project->getAllSshKeys()}
                {if $sshKeys}
                <table class="table table-responsive table-striped">
                    <thead>
                        <tr>
                            <th class="action"></th>
                            <th>{translate key="label.ssh-key"}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach $sshKeys as $sshKey}
                        <tr>
                            <td class="action">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="{url id="projects.ssh-keys.delete" parameters=["project" => $project->getCode(), "sshKey" => $sshKey->getId()]}{$referer}">{translate key="button.delete"}</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                {$sshKey->getLabel()}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                {else}
                <p>{translate key="label.project.ssh-keys.none"}</p>
                {/if}

                <p>
                    <a href="{url id="projects.ssh-keys" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-lock"></span>
                        {translate key="button.ssh-keys.manage"}
                    </a>
                </p>
            </div>

            <div role="tabpanel" class="tab-pane" id="environments">
                {$referer = "`$app.url.request`#environments"|urlencode}
                {$referer = "?referer=`$referer`"}

                {if $project->getEnvironments()}
                <table class="table table-responsive table-striped">
                    <thead>
                        <tr>
                            <th class="action"></th>
                            <th>{translate key="label.domain"}</th>
                            <th class="hidden-xs">{translate key="label.alias"}</th>
                            <th class="hidden-xs">{translate key="label.version.php"}</th>
                            <th class="hidden-xs">{translate key="label.server"}</th>
                            <th class="hidden-xs">{translate key="label.disk.usage"}</th>
                            <th>{translate key="label.active"}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach $project->getEnvironments() as $environment}
                        <tr>
                            <td class="action">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="{url id="environments.php" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">{translate key="button.php.settings"}</a></li>
                                        <li><a href="{url id="environments.ssl" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">{translate key="button.ssl.settings"}</a></li>
                                        <li><a href="{url id="environments.log" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">{translate key="button.log.view"}</a></li>
                                        <li><a href="{url id="environments.backups" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">{translate key="button.backup.view"}</a></li>
                                        <li role="separator" class="divider"></li>
                                        <li><a href="{url id="environments.edit" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">{translate key="button.edit"}</a></li>
                                        <li><a href="{url id="environments.delete" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">{translate key="button.delete"}</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="{url id="environments.edit" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">
                                    {$environment->getDomain()}
                                </a>
                                {call queueLabel jobStatus=$environment->getQueueJobStatus() isDeleted=$environment->isDeleted() container="#environments"}

                                <div class="visible-xs">
                                    <small class="text-muted">{$environment->getServer()->getHost()}</small>
                                </div>
                            </td>
                            <td class="hidden-xs">
                                <div><a href="http{if $environment->isSslActive() && $environment->getSslCommonName() == $environment->getDomain()}s{/if}://{$environment->getDomain()}" target="_blank">{$environment->getDomain()}</a></div>
                                {foreach $environment->getAliases() as $alias}
                                    <div><a href="http{if $environment->isSslActive() && $environment->getSslCommonName() == $alias}s{/if}://{$alias}" target="_blank">{$alias}</a></div>
                                {/foreach}
                                </ul>
                            </td>
                            <td class="hidden-xs">
                                <a href="{url id="environments.php" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">
                                    {$environment->getPhpVersion()}
                                </a>
                            </td>
                            <td class="hidden-xs">
                                {$server = $environment->getServer()}
                                {$server->getHost()}
                                {if $server->getHost() != $server->getIpAddress()}
                                <br>
                                <small>{$server->getIpAddress()}</small>
                                {/if}
                            </td>
                            <td class="hidden-xs">
                                {$environment->getDiskUsage()|decorate:"storage.size"}
                            </td>
                            <td>
                            {if $environment->isSslActive()}
                                {if $environment->isValidSsl()}
                                {$label = 'success'}
                                {else}
                                {$label = 'danger'}
                                {/if}
                            {else}
                                {$label = 'warning'}
                            {/if}

                            <a href="{url id="environments.ssl" parameters=["project" => $project->getCode(), "environment" => $environment->getId()]}{$referer}">
                                <span class="label label-{$label}">{translate key="label.ssl"}</span>
                            </a>

                            {if $environment->isActive()}
                                <span class="label label-success">{translate key="label.active"}</span>
                            {else}
                                <span class="label label-danger">{translate key="label.inactive"}</span>
                            {/if}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                {else}
                <p>{translate key="label.environments.none"}</p>
                {/if}
                <p>
                    <a href="{url id="environments.add" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-plus"></span>
                        {translate key="button.environment.add"}
                    </a>
                </p>
            </div>

            <div role="tabpanel" class="tab-pane" id="databases">
                {$referer = "`$app.url.request`#databases"|urlencode}
                {$referer = "?referer=`$referer`"}

                {if $project->getDatabases()}
                    <table class="table table-responsive table-striped">
                    <thead>
                        <tr>
                            <th class="action"></th>
                            <th>{translate key="label.name"}</th>
                            <th class="hidden-xs">{translate key="label.server"}</th>
                            <th class="hidden-xs">{translate key="label.disk.usage"}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach $project->getDatabases() as $database}
                        <tr>
                            <td class="action">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="{url id="databases.backups" parameters=["project" => $project->getCode(), "database" => $database->getId()]}{$referer}">{translate key="button.backup.view"}</a></li>
                                        <li role="separator" class="divider"></li>
                                        <li><a href="{url id="databases.delete" parameters=["project" => $project->getCode(), "database" => $database->getId()]}{$referer}">{translate key="button.delete"}</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                {$database->getDatabaseName()}
                                {call queueLabel jobStatus=$database->getQueueJobStatus() isDeleted=$database->isDeleted() container="#databases"}

                                <div class="visible-xs">
                                    <small class="text-muted">{$database->getServer()->getHost()}</small>
                                </div>
                            </td>
                            <td class="hidden-xs">
                                mysql://{$project->getUsername()}:{$password}@{$database->getServer()->getHost()}/{$database->getDatabaseName()}
                            </td>
                            <td class="hidden-xs">
                                {$database->getDiskUsage()|decorate:"storage.size"}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                {else}
                <p>{translate key="label.databases.none"}</p>
                {/if}

                <p>
                    <a href="{url id="databases.add" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-plus"></span>
                        {translate key="button.database.add"}
                    </a>
                </p>
            </div>

            <div role="tabpanel" class="tab-pane" id="cron">
            {$referer = "`$app.url.request`#cron"|urlencode}
            {$referer = "?referer=`$referer`"}

            {if $project->getCronTab()}
                <p>{translate key="label.cron.description" server=$project->getCronServer()->getHost()}</p>
                <pre>{$project->getCronTab()}</pre>

                <p>
                    <a href="{url id="projects.cron" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-time"></span>
                        {translate key="button.cron.edit"}
                    </a>
                </p>
            {else}
                <p>{translate key="label.cron.none"}</p>

                <p>
                    <a href="{url id="projects.cron" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-time"></span>
                        {translate key="button.cron.add"}
                    </a>
                </p>
            {/if}
            </div>

            <div role="tabpanel" class="tab-pane" id="varnish">
            {$referer = "`$app.url.request`#varnish"|urlencode}
            {$referer = "?referer=`$referer`"}

            {if $project->getVarnishServer()}
                {$varnishMemory = "memory.`$project->getVarnishMemory()`"}

                <p>{translate key="label.varnish.description" memory=$varnishMemory|translate}</p>
                <dl>
                    <dt>{translate key="label.varnish.listen"}</dt>
                    <dd>
                        {$ipAddress = $project->getVarnishServer()->getIpAddress()}
                        {$serverAddress = $ipAddress}
                        {$projectAddress = "`$serverAddress`:`$project->getVarnishPort()`"}

                        {$serverPort = $project->getVarnishServer()->getVarnishPort()}
                        {if $serverPort != 80}
                            {$serverAddress = "`$serverAddress`:`$serverPort`"}
                        {/if}

                        <div>
                            <a href="http://{$projectAddress}">
                                {$projectAddress}
                            </a>
                        <div>
                        <div>
                            <a href="http://{$serverAddress}">
                                {$serverAddress}
                            </a>
                        <div>
                    </dd>
                    <dt>{translate key="label.varnish.admin"}</dt>
                    <dd>{$project->getVarnishServer()->getIpAddress()}:{$project->getVarnishAdminPort()}</dd>
                    <dt>{translate key="label.secret"}</dt>
                    <dd>{$varnishSecret}</dd>
                </dl>

                <p>
                    <a href="#" class="varnish-vcl-toggle">
                        {translate key="button.vcl.toggle"}
                    </a>
                </p>
                <div class="varnish-vcl">
                    <pre>{$project->getVarnishVcl()}</pre>
                </div>

                {if $project->getVarnishMemory()}
                <div class="btn-group clearfix">
                    <a href="{url id="projects.varnish" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-cog"></span>
                        {translate key="button.varnish.edit"}
                    </a>
                    <a href="{url id="projects.varnish.restart" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-repeat"></span>
                        {translate key="button.varnish.restart"}
                    </a>
                    <a href="{url id="projects.varnish.delete" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-off"></span>
                        {translate key="button.varnish.delete"}
                    </a>
                </div>
                {/if}
            {else}
                <p>{translate key="label.varnish.none"}</p>

                <p>
                    <a href="{url id="projects.varnish" parameters=["project" => $project->getCode()]}{$referer}" class="btn btn-default">
                        <span class="glyphicon glyphicon-flash"></span>
                        {translate key="button.varnish.add"}
                    </a>
                </p>
            {/if}
            </div>

            <div role="tabpanel" class="tab-pane" id="messages">
            {if $messages}
                <table class="table table-responsive table-striped">
                    <tbody>
                {foreach $messages as $message}
                    {$type = $message->getType()}
                    {if $type == 'error'}
                        {$type = 'danger'}
                    {/if}
                        <tr>
                            <td class="action hidden-xs">
                                <span class="label label-{$type}">{$message->getType()}</span>
                            </td>
                            <td class="action text-center">
                                <span class="visible-xs label label-{$type}">{$message->getType()}</span>
                                <small>
                                    {$message->getDateAdded()|date_format:"%Y/%m/%d %H:%M:%S"}
                                </small>
                            </td>
                            <td>
                                <a href="{url id="messages.detail" parameters=["id" => $message->getId()]}">
                                    {$message->getTitle()}
                                </a>
                                <br>
                                <small>{$message->getDescription()|replace:" ":"&nbsp;"|nl2br}</small>
                            </td>
                        </tr>
                {/foreach}
                    </tbody>
                </table>
            {else}
                <p>{translate key="label.messages.none"}</p>
            {/if}
            </div>
        </div>
    </div>
{/block}
