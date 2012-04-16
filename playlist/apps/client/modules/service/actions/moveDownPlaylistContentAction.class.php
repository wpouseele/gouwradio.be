<?php
#
# Move down Content in the user's playlist 
#
class moveDownPlaylistContentAction extends sfAction
{
  public function execute($request)
  {
  		//validate required fields
    	if ( !$request->getParameter( 'playlist_id' ) ) $this->forward404();

    	//move down playlist content
		Doctrine_Core::getTable('PlaylistFiles')->moveDownPlaylistFile( $request->getParameter( 'playlist_id' ), $request->getParameter( 'id' ) );
		return sfView::NONE;
  }
}