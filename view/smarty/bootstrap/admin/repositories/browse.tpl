{extends file="base/index"}

{block name="head_title" prepend}{$repository->getName()} | {translate key="title.repositories"} | {/block}

{block name="styles" append}
    {style src="bootstrap/css/repository.css"}
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
    {include file="admin/repositories/helper.header" url="repositories.browse"}
    {include file="admin/repositories/helper.browse"}

    <table class="table table-bordered table-striped">
    {foreach $files as $file}
        <tr>
            <td>
                <span class="glyphicon glyphicon-{if $file->isDirectory()}folder-open{else}file{/if}"></span>
                &nbsp;
                <a href="{url id="repositories.browse" parameters=['repository' => $repository->getSlug(), 'branch' => $branch]}{$path}/{$file->getName()}">{$file->getName()}</a>
            </td>
            <td class="hidden-xs">
                {$file->commit->message}
            </td>
            <td class="hidden-xs">{$file->commit->date}</td>
            <td>
                <div class="pull-right">
                    <a href="{url id="repositories.download" parameters=['repository' => $repository->getSlug(), 'branch' => $branch, 'revision' => $file->commit->revision]}">{$file->commit->getFriendlyRevision()}</a>
                </div>
            </td>
        </tr>
    {/foreach}
    </table>
{/block}
