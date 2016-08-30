{extends file="base/index"}

{block name="head_title" prepend}{$repository->getName()} | {translate key="title.repositories"} | {/block}

{block name="scripts_inline" append}
<script type="text/javascript">
    function updateQueueStatus(url, sleepTime) {
        $.get(url, function(data) {
            if (data.status == 'error') {
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
        var url = "{url id="api.queue.status.job" parameters=["queue" => "%queue%", "id" => "%id%"]}";

        $("[data-queue]").each(function() {
            var $this = $(this);

            var jobUrl = url;
            jobUrl = jobUrl.replace('%25queue%25', $this.data('queue'));
            jobUrl = jobUrl.replace('%25id%25', $this.data('queue-id'));

            updateQueueStatus(jobUrl, 3);
        });
    });
</script>
{/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.repositories"}
            <small>
                {$repository->getName()}
            </small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="admin/repositories/helper.header" url="repositories.deployment"}

    <div class="tab-content">

    {$referer = $app.url.request|urlencode}
    {$referer = "?referer=`$referer`"}

    {if $deployers}
        <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.name"}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        {foreach $deployers as $deployer}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="repositories.deployment.edit" parameters=["repository" => $repository->getSlug(), "deployer" => $deployer->getId()]}/{$branch}{$referer}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="repositories.deployment.delete" parameters=["repository" => $repository->getSlug(), "deployer" => $deployer->getId()]}/{$branch}{$referer}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="repositories.deployment.edit" parameters=["repository" => $repository->getSlug(), "deployer" => $deployer->getId()]}/{$branch}{$referer}">
                        {$deployer->getName()}
                    </a>
                    <div class="text-muted">{$deployer->getRepositoryPath()} &rarr; {$deployer->getDsn()}</div>
                </td>
                <td class="text-right">
                {$label = null}
                {$translation = null}
                {$attributes = ""}
                {$jobStatus = $deployer->getQueueJobStatus()}

                {if $jobStatus || $deployer->isWorking()}
                    {$label = 'warning'}
                    {$translation = 'label.status.working'}

                    {if $jobStatus}
                        {$attributes = " data-queue=\"`$jobStatus->getQueue()`\" data-queue-id=\"`$jobStatus->getId()`\""}
                    {/if}
                {elseif $deployer->getDateDeployed()}
                    {if $deployer->isError()}
                        {$label = 'danger'}
                        {$translation = 'label.status.error'}
                    {else}
                        {$label = 'success'}
                        {$translation = 'label.status.ok'}
                    {/if}
                {/if}

                {if $label}
                <span class="label label-{$label}"{$attributes}>
                    {translate key=$translation}
                </span>
                &nbsp;
                {/if}
                {if $deployer->getDateDeployed()}
                    {$deployer->getFriendlyRevision()}
                    <br>
                    <small>
                        <span class="text-muted">{$deployer->getDateDeployed()|date_format:"%Y/%m/%d %H:%M:%S"}</span>
                    </small>
                {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {else}
    <p>{translate key="label.deployers.none"}</p>
    {/if}

    <div class="btn-group">
        <a href="{url id="repositories.deployment.add" parameters=["repository" => $repository->getSlug()]}/{$branch}{$referer}" class="btn btn-default">
            <span class="glyphicon glyphicon-plus"></span>
            {translate key="button.deployer.add"}
        </a>
        {if $deployers}
        <a href="{url id="repositories.deployment.deploy" parameters=["repository" => $repository->getSlug()]}/{$branch}{$referer}" class="btn btn-default">
            <span class="glyphicon glyphicon-upload"></span>
            {translate key="button.deploy"}
        </a>
        {/if}
    </div>

    </div>
{/block}
