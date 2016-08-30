{extends file="base/index"}

{block name="head_title" prepend}{translate key="title.repositories"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.repositories"}</h1>
    </div>
{/block}

{block name="content" append}
    <p>
        <a href="{url id="repositories.add"}?referer={$app.url.request|urlencode}" class="btn btn-default">
            <span class="glyphicon glyphicon-plus"></span> {translate key="button.repository.add"}
        </a>
    </p>

    <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.name"}</th>
                <th>{translate key="label.url"}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
    {foreach $repositories as $repository}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="repositories.detail" parameters=["repository" => $repository->getSlug()]}">
                                    {translate key="button.view.detail"}
                                </a>
                            </li>
                            <li role="separator" class="divider"></li>
                            <li>
                                <a href="{url id="repositories.edit" parameters=["repository" => $repository->getSlug()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="repositories.delete" parameters=["repository" => $repository->getSlug()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="repositories.detail" parameters=["repository" => $repository->getSlug()]}">
                        {$repository->getName()}
                    </a>
                </td>
                <td>
                    {$repository->getUrl()}
                </td>
                <td>
                    {if $repository->isError()}
                        <span class="label label-danger">{translate key="label.status.error"}</span>
                    {elseif $repository->isWorking() || $repository->isNew()}
                        <span class="label label-warning">{translate key="label.status.working"}</span>
                    {elseif $repository->isReady()}
                        <span class="label label-success">{translate key="label.status.ok"}</span>
                    {/if}
                </td>
            </tr>
    {/foreach}
        </tbody>
    </table>
{/block}
