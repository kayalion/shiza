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
    {include file="admin/repositories/helper.header" url="repositories.integration"}

    <div class="tab-content">

    {$referer = $app.url.request|urlencode}
    {$referer = "?referer=`$referer`"}

    {if $builders}
        <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.name"}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        {foreach $builders as $builder}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="repositories.integration.edit" parameters=["repository" => $repository->getSlug(), "builder" => $builder->getId()]}/{$branch}{$referer}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="repositories.integration.delete" parameters=["repository" => $repository->getSlug(), "builder" => $builder->getId()]}/{$branch}{$referer}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="repositories.integration.edit" parameters=["repository" => $repository->getSlug(), "builder" => $builder->getId()]}/{$branch}{$referer}">
                        {$builder->getName()}
                    </a>
                </td>
                <td class="text-right">
                {$label = null}
                {$translation = null}
                {$attributes = ""}
                {$jobStatus = $builder->getQueueJobStatus()}

                {if $jobStatus || $builder->isWorking()}
                    {$label = 'warning'}
                    {$translation = 'label.status.working'}

                    {if $jobStatus}
                        {$attributes = " data-queue=\"`$jobStatus->getQueue()`\" data-queue-id=\"`$jobStatus->getId()`\""}
                    {/if}
                {elseif $builder->getDateBuilded()}
                    {if $builder->isError()}
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
                {if $builder->getDateBuilded()}
                    {$builder->getFriendlyRevision()}
                    <br>
                    <small>
                        <span class="text-muted">{$builder->getDateBuilded()|date_format:"%Y/%m/%d %H:%M:%S"}</span>
                    </small>
                {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {else}
    <p>{translate key="label.builders.none"}</p>
    {/if}

    <div class="btn-group">
        <a href="{url id="repositories.integration.add" parameters=["repository" => $repository->getSlug()]}/{$branch}{$referer}" class="btn btn-default">
            <span class="glyphicon glyphicon-plus"></span>
            {translate key="button.builder.add"}
        </a>
        {if $builders}
        <a href="{url id="repositories.integration.build" parameters=["repository" => $repository->getSlug()]}/{$branch}{$referer}" class="btn btn-default">
            <span class="glyphicon glyphicon-wrench"></span>
            {translate key="button.build"}
        </a>
        {/if}
    </div>

    </div>
{/block}
