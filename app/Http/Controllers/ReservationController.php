<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Datetime;

class ReservationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function store(Request $request)
     {
        $request->validate([
            'num_of_people' => 'required|integer'  ,
            'reservation_date' => 'required|date' ,
            'reservation_time' => 'required'
        ]);

        $date_time = new DateTime($request->input('reservation_date').' '. $request->input('reservation_time').':00');
        
        if (new DateTime() > $date_time) {
            return back()->withInput($request->input())->withErrors(['message' => '現在より過去の予約日時は指定できません。']);
        }

        $store = Store::find($request->store_id);
        if($store->open_time > $date_time->format('H:i') || $store->close_time < $date_time->format('H:i')){
            return back()->withInput($request->input())->withErrors(['message' => '営業時間外です。']);
        }

        foreach(explode(',',$store->holiday) as $holiday){
            if(array_search($holiday,Store::DAY_OF_WEEK) == $date_time->format('w')){
                return back()->withInput($request->input())->withErrors(['message' => '定休日です。']);
            }
        }

        // 予約を作成
        $reservation = new Reservation();
        $reservation->user_id = $request->input('user_id');
        $reservation->store_id = $request->input('store_id');
        $reservation->reservation_date = $request->input('reservation_date');
        $reservation->reservation_time = $request->input('reservation_time');
        $reservation->num_of_people = $request->input('num_of_people');
        $reservation->save();

        // 予約が完了すると店舗一覧に戻り、予約完了のメッセージを表示する

       return redirect()->route('stores.index')->with('success', '予約が完了しました。');
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        
        return to_route('mypage.reservations');
    }
}
