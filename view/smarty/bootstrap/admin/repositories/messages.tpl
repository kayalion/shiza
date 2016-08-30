{extends file="base/index"}

{block name="head_title" prepend}{$repository->getName()} | {translate key="title.repositories"} | {/block}

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
    {include file="admin/repositories/helper.header" url="repositories.messages"}

    <div class="tab-content">
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
{/block}
