{extends file="base/index"}

{block name="head_title" prepend}{translate key="title.projects"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.projects"}</h1>
    </div>
{/block}

{block name="content" append}
    <p>
        <a href="{url id="projects.add"}?referer={$app.url.request|urlencode}" class="btn btn-default">
            <span class="glyphicon glyphicon-plus"></span> {translate key="button.project.add"}
        </a>
    </p>

    <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.code"}</th>
                <th>{translate key="label.project"}</th>
            </tr>
        </thead>
        <tbody>
    {foreach $projects as $project}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="projects.detail" parameters=["project" => $project->getCode()]}">
                                    {translate key="button.view.detail"}
                                </a>
                            </li>
                            <li role="separator" class="divider"></li>
                            <li>
                                <a href="{url id="projects.edit" parameters=["project" => $project->getCode()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="projects.delete" parameters=["project" => $project->getCode()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="projects.detail" parameters=["project" => $project->getCode()]}">
                        {$project->getCode()}
                    </a>
                </td>
                <td>
                    {$project->getName()}
                </td>
            </tr>
    {/foreach}
        </tbody>
    </table>
{/block}
